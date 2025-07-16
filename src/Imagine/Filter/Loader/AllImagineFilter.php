<?php

namespace App\Imagine\Filter\Loader;

use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;

//documentation on https://imagine.readthedocs.io/en/stable/usage/effects.html

class AllImagineFilter implements LoaderInterface
{
    /**
     * @return ImageInterface
     */
    public function load(ImageInterface $image, array $taboptions = [])
    {
        foreach (explode(',', $taboptions[0]) as $opts) {
            if (isset($opts)) {
                $options = explode(':', $opts);
                $value = isset($options[1]) ? $options[1] : null;
                $effect = $options[0];
                switch ($options[0]) {
                    case 'colorize':
                        $value = $image->palette()->color($options[1]);
                        break;
                }
                $image->effects()->$effect($value);
            }
        }
        return $image;
    }
}
