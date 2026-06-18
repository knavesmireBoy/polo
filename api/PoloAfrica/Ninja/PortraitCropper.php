<?php

namespace Ninja;

class PortraitCropper
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
        $res = $this->height / $this->width;
        //h too big crop top/bottom

        if (greaterThan($res, $this->ratio)) {
            if (!empty($bg)) {
                return $this->pad();
             }
            $target_height = $this->width * $this->ratio;
            $this->src_y = $this->calc($this->height, $target_height, $this->offset);
            $this->height = $target_height;
        }
        //w too big crop sides
        if (lesserThan($res, $this->ratio)) {
            if (!empty($bg)) {
                return $this->pad();
            }
            $target_width = intval($this->height / $this->ratio);
            $this->src_x = $this->calc($this->width, $target_width, $this->offset);
            $this->width = $target_width;
        }
    }

    public function pad()
    {
        $res = $this->height / $this->width;
        $square = true;
        if (greaterThan($res, $this->ratio)) {
            $target_width = $this->height / $this->ratio;
            $this->src_x = $this->calc($this->width, $target_width, $this->offset);
            $this->width = $target_width;
            $this->src_y--;
            $square = false;
        }
        if (lesserThan($res, $this->ratio)) {
            $target_height = intval($this->width * $this->ratio);
            $this->src_x = 0.5;
            $this->src_y = $this->calc($this->height, $target_height, $this->offset);
            $this->height = $target_height;            
            $square = false;
        }
        else if($square) {//exactly square
            //convert portrait to landscape
            $x = 1 / $this->ratio;
            $target_width = $this->height / $x;
            $this->src_x = $this->calc($this->width, $target_width, $this->offset);
            $this->width = $target_width;
            //!!! and for some reason adding 1 to the HEIGHT when invoking imagecreatetruecolor prevents the split fill issue
            $this->adj = 1;
        }
    }
}
