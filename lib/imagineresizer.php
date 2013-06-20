<?php

// http://giorgiocefaro.com/blog/using-php-imagine-to-best-fit-crop-images

use Symfony\Component\HttpFoundation\File\File;
use Imagine\Image\ImagineInterface;
use Imagine\Image\BoxInterface;
use Imagine\Image\Point;
use Imagine\Image\Box;

/*
Example usage:
$imagine = new ImagineResizer($full_path);

if ($image['image_width'] > $settings['width'] or $image['image_height'] > $settings['height'])
{
	$imagine->cropResize($settings['upload_path'], $settings['width'], $settings['height']);
}

if ($settings['thumb'])
{
	$imagine->cropResize($settings['thumb_upload_path'], $settings['thumb_width'], $settings['thumb_height']);
}
*/

class ImagineResizer
{
	protected $imagine;
	protected $mode;
	protected $box;
	protected $file;
	protected $full_path;

	public function __construct($full_path)
	{
		$this->file = new File($full_path);

		$this->validateImage(); // validating an image

		$this->full_path = $this->file->getRealPath();
	}

	public function cropResize($destination, $source_width = null, $source_height = null, $quality = 90)
	{
		( ! $source_width and $source_height) and $source_width = $this->autoWidth($source_height);
		( ! $source_height and $source_width) and $source_height = $this->autoHeight($source_width);

		$imagine = new Imagine\Gd\Imagine();
		$box = new Box($source_width, $source_height);
		$destination = rtrim($destination, '/').'/';

		$filename = $this->file->getFileName();
		$image = $imagine->open($this->file);

		//original size
		$srcBox = $image->getSize();

		if ($srcBox->getWidth() < $source_width and $srcBox->getHeight() < $source_height)
		{
			$dest = $destination.$this->file->getFileName();

			// only if paths are different
			if ($dest != $this->full_path)
			{
				if ( ! copy($this->file, $dest))
				{

					throw new Exception("failed to copy from $this->file to {$dest}...\n");
				}

				@chmod($dest, 0666);
			}

			return $this->file->getFileName();
		}

		//we scale on the smaller dimension
		if ($srcBox->getWidth() > $srcBox->getHeight()) {
			$width  = $srcBox->getWidth() * ($box->getHeight() / $srcBox->getHeight());
			$height =  $box->getHeight();
			//we center the crop in relation to the width
			$cropPoint = new Point((max($width - $box->getWidth(), 0))/2, 0);
		} else {
			$width  = $box->getWidth();
			$height =  $srcBox->getHeight() * ($box->getWidth() / $srcBox->getWidth());
			//we center the crop in relation to the height
			$cropPoint = new Point(0, (max($height - $box->getHeight(),0)) / 2);
		}

		$image = $image->thumbnail($box, \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND);

		$image
			//->crop($cropPoint, new Box($width, $height))
			->save($destination.$filename, array('quality' => $quality));

		@chmod($destination.$filename, 0666);

		return $this->file->getFileName();
	}

	protected function validateImage()
	{
		$mime = $this->file->getMimeType();
		if (strpos($mime, 'image/') !== 0)
		{
			throw new Exception("Not an image: {$mime}...\n");
		}

		return true;
	}

	protected function autoWidth($height)
	{
		list ($originalWidth, $originalHeight) = getimagesize($this->full_path);

		$ratio = bcdiv($originalHeight, $height, 3);

		return bcdiv($originalWidth, $ratio, 0);
	}

	protected function autoHeight($width)
	{
		list ($originalWidth, $originalHeight) = getimagesize($this->full_path);

		$ratio = bcdiv($originalWidth, $width, 3);

		return bcdiv($originalHeight, $ratio, 0);
	}

}

