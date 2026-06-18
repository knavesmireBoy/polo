<?php

namespace Ninja;

class CropperFactory
{
  public $cropper;

  public function __construct($width, $height, $ratio, $offset, $portrait = false)
  {
    if ($portrait) {
      $this->cropper = new PortraitCropper($width, $height, $ratio, $offset);
    } else {
      $this->cropper = new LandscapeCropper($width, $height, $ratio, $offset);
    }
  }
}
