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
	$imagine->cropResize($destination_dir, $width, $height);
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

	public function cropResize($destination, $dest_width = null, $dest_height = null, $quality = 90)
	{
		if ( ! $dest_width and ! $dest_height)
		{
			throw new Exception("\$dest_width or \$dest_height is required.\n");
		}

		( ! $dest_width and $dest_height) and $dest_width = $this->autoWidth($dest_height);
		( ! $dest_height and $dest_width) and $dest_height = $this->autoHeight($dest_width);

		$imagine = new Imagine\Gd\Imagine();
		$box = new Box($dest_width, $dest_height);

		$new_filename = pathinfo($destination, PATHINFO_FILENAME);
		$new_extention = pathinfo($destination, PATHINFO_EXTENSION);

		$filename = $this->file->getFileName();

		// extension exists - change destination filename
		if ($new_extention and $new_filename)
		{
			$filename = $new_filename.'.'.$new_extention;

			// remove filename from destination
			$destination = substr($destination, 0, -strlen($filename));
		}

		$destination = rtrim($destination, '/').'/';
		$image = $imagine->open($this->file);

		// original size
		$srcBox = $image->getSize();

		if ($srcBox->getWidth() < $dest_width and $srcBox->getHeight() < $dest_height)
		{
			$dest = FCPATH.$destination.$this->file->getFileName();

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

	public function resizeBg($destination, $dest_width = null, $dest_height = null, $quality = 90, $bgcolor = '#fff')
	{
		if ( ! $dest_width and ! $dest_height)
		{
			throw new Exception("\$dest_width or \$dest_height is required.\n");
		}

		$imagine = new Imagine\Gd\Imagine();
		$box = new Box($dest_width, $dest_height);

		$new_filename = pathinfo($destination, PATHINFO_FILENAME);
		$new_extention = pathinfo($destination, PATHINFO_EXTENSION);

		$filename = $this->file->getFileName();

		// extension exists - change destination filename
		if ($new_extention and $new_filename)
		{
			$filename = $new_filename.'.'.$new_extention;

			// remove filename from destination
			$destination = substr($destination, 0, -strlen($filename));
		}

		$destination = rtrim($destination, '/').'/';
		$image = $imagine->open($this->file);

		$srcBox = $image->getSize();

		$source_width = $srcBox->getWidth();
		$source_height = $srcBox->getHeight();

		$valid_width = $dest_width;
		$valid_height = $dest_height;

		if ($dest_width or $dest_height)
		{
			$divider_x = bcdiv($source_width, $dest_width, 3);
			$divider_y = bcdiv($source_height, $dest_height, 3);
			
			$divider = ($divider_x >= $divider_y) ? $divider_x : $divider_y;
			
			$valid_width = floor(bcdiv($source_width, $divider, 3));
			$valid_height = floor(bcdiv($source_height, $divider, 3));
		}

		$offset_x = 0;
		$offset_y = 0;

		if ($dest_width > $valid_width)
		{
			$sub = bcsub($dest_width, $valid_width);
			$offset_x = floor(bcdiv($sub, 2, 3));
		}

		if ($dest_height > $valid_height)
		{
			$sub = bcsub($dest_height, $valid_height);
			$offset_y = floor(bcdiv($sub, 2, 3));
		}

		$image->resize(new Box($valid_width, $valid_height));

		$container = $imagine->create(new Box($dest_width, $dest_height));

		$container->paste($image, new Point($offset_x, $offset_y));
		$container->save($destination.$filename, array('quality' => $quality));

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
