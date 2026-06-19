<?php

namespace PoloAfrica\Controllers;

//include_once 'config.php';

use \Ninja\DatabaseTable;
use \Ninja\Strategy\Checker;
use \Ninja\Strategy\Negator;
use \Ninja\Strategy\Empti;
use \Ninja\Strategy\PhoneNumber;
use \Ninja\Strategy\isName;
use \Ninja\Strategy\isEmail;
use \Ninja\Strategy\isMatch;
use \Ninja\Strategy\isSmallMsg;
use \Ninja\Strategy\isLargeMsg;
use \Ninja\Composite\Composite;

use \Michelf\MarkdownExtra;

class Contact
{
    public function __construct()
    {
    }

    public function process()
    {
        return $this->processForm();
    }
    protected function processForm()
    {
        $host = 'north.wolds@btinternet.com';
        $to = 'andrewsykes@btinternet.com';
        $expected = array(
            'name',
            'email',
            'emailconfirm',
            'phone',
            'comment'
        );
        $text = "Please use this area for comments or questions";
        $post_text = 'Please enter your message';
        $klas = 'empty';
        $mailsent = false;
        $missing = [];
        $mailnotsent = '';
        $suspect = false;
        $data = [];
        $pairs = array(
            'phone' => 'email'
        );
        $firstname = '';
        $item = 'item';
        $empty = new Checker("The required fields are indicated", new Negator(new Empti()));
        $subtext = substr($text, 0, 13);
        $subpost_text = substr($post_text, 0, 13);
        $isNum = new Checker('Please supply a phone number', new PhoneNumber());
        $isEmail = new Checker('Please supply a valid email address', new isEmail());
        $isName = new Checker('Please supply name in the expected format: "FirstName [MiddleName] LastName"', new isName());
        $isSmallMsg = new Checker('Your message is very small, please elaborate', new isSmallMsg());
        $isLargeMsg = new Checker('The word count of your message is too great, reduce or please call instead', new isLargeMsg());
        $comment = new Checker($post_text, new Negator(new isMatch("/^$subtext/")));
        $postcomment = new Checker($post_text, new Negator(new isMatch("/^$subpost_text/")));
        $required = array(
            'firstname' => preconditions($empty, $isName),
            'surname' => preconditions($empty, $isName),
            'email' => preconditions($empty, $isEmail),
            'comment' => preconditions($empty, $comment, $postcomment, $isSmallMsg, $isLargeMsg)
        );

        if (!empty($_POST['details'])) {
            $message = '';
            $data = array_map('spam_scrubber', $_POST['details']);
            $suspect = !empty(array_filter($data, 'single_space'));
            //honeypot
            if (!$suspect && $_POST['url']) {
                $suspect = true;
            }
            if (!$suspect) {
                $val = $data['email'];
                $isMatch = new Checker('emails must match', new isMatch("/^$val$/"));
                $required['emailconfirm'] = preconditions($isMatch);

                foreach ($data as $k => $v) {
                    if (isset($required[$k])) {
                        $res = $required[$k]('identity', $v);
                        //$res will be a string if valid, or an array of issues
                        if (is_array($res)) {
                            $missing[$k] = $res;
                            $k = null;
                        }
                    }
                    if (in_array($k, $expected)) {
                        //sets vars used below, $email, $comments
                        ${$k} = trim($v);
                        $message .= buildMessage($k, $v, $k === 'comment');
                    }
                } //each
            }
            if (empty($missing)) {
                $message = wordwrap($message, 70);
                $headers = "From: $host";
                $headers .= "\r\nContent-Type: text/plain; charset=utf-8";
                $headers .= "\r\nReply-To: $email";
                //$mailsent = mail($host, 'Website Enquiry', $message, $headers);
                $mailsent = true;
                $klas = 'success';
                if ($mailsent) {
                    unset($missing);
                } else {
                    $mailnotsent = '<h1>Sorry, There was a problem sending your message. Please try again later.</h1>';
                } //not sent
            } //ok
            else {
                $item = count($missing) > 1 ? 'items' : 'item';
                $msg = "Please complete the missing $item indicated";
                $klas = 'warning ';
                //sort...
                $keys = array_keys($missing);
                $msg = array_values($missing)[0][0];
                $klas .= $keys[0];
                //https://stackoverflow.com/questions/24403817/html5-required-attribute-one-of-two-fields;
            }
        } //posted
        //NOTE variables created on-the-fly for $email(line 327) and $comments
        return [
            'template' => 'contact.html.php',
            'title' => ucfirst('enquiries'),
            'variables' => [
                'klas' => $klas,
                'mailsent' => $mailsent,
                'mailnotsent' => $mailnotsent,
                'msg' => $msg ?? 'Do Keep In Touch!',
                'missing' => $missing ?? [],
                'firstname' => $data['firstname'] ?? '',
                'surname' => $data['surname'] ?? '',
                'phone' => $data['phone'] ?? '',
                'myemail' => $data['email'] ?? '',
                'myemailconfirm' => $data['emailconfirm'] ?? '',
                'mycomment' => $data['comment'] ?? '',
                'email' => $email ?? 'andrewsykes@btinternet.com'
            ]
        ];
    }
}
