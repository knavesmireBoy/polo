<?php

namespace Ninja;

class LandscapeCropper
{
    public $width;
    public $height;
    public $offset;
    public $adj = 0;
    public $src_x = 0;
    public $src_y = 0;
    protected $ratio;
    protected $bg;

    public function __construct($width, $height, $ratio, $offset)
    {
        $this->width = $width;
        $this->height = $height;
        $this->ratio = $ratio;
        $this->offset = $offset;
    }

    private function calc($old, $new, $fr = 0.5)
    {
        return ($old - $new) * $fr;
    }
    public function crop($bg = [])
    {
        $res = $this->width / $this->height;
        //w too big crop sides
        if (greaterThan($res, $this->ratio)) {
            if (!empty($bg)) {
                return $this->pad();
             }
            $target_width = $this->height * $this->ratio;
            $this->src_x = $this->calc($this->width, $target_width, $this->offset);
            $this->width = $target_width;
           
        }
        //h too big crop top/bottom
        if (lesserThan($res, $this->ratio)) {
            if (!empty($bg)) {
                return $this->pad();
             }
            $target_height = $this->width / $this->ratio;
            $this->src_y = $this->calc($this->height, $target_height, $this->offset);
            $this->height = $target_height;
        }
    }

    public function pad()
    {
        $res = $this->width / $this->height;
        if (greaterThan($res, $this->ratio)) { //pad vertical
            $target_height = intval($this->width / $this->ratio);
            $this->src_y = $this->calc($this->height, $target_height, $this->offset);
            $this->height = $target_height;
        }
        if (lesserThan($res, $this->ratio)) { //pad horizontal
            $target_width = intval($this->height * $this->ratio);
            $this->src_x = $this->calc($this->width, $target_width, $this->offset);
            //!!! for some reason have to make a slight adjustment otherwise the background fill doesn't work as expected
            $this->src_y--;
            $this->width = $target_width;
        }
    }
}
