<?php

/*
Plugin Name: Optimize uploaded images
Plugin URI: https://github.com/lstellway
Description: Optimize uploaded media
Version: 0.1.0
Author: Logan Stellway
Author URI: https://loganstellway.com
*/

namespace LStellway\Uploads;

use ImageOptimizer\Optimizer;
use ImageOptimizer\OptimizerFactory;

if (!defined('ABSPATH') || !is_blog_installed()) {
    return;
}

class OptimizeImages
{
    /**
     * @var Optimizer
     */
    private $optimizer;

    /**
     * @var array
     */
    private $optimizerOptions = [
        'PS_IMAGE_OPTIMIZER_BIN_ADVPNG'    => 'advpng_bin',
        'PS_IMAGE_OPTIMIZER_BIN_GIFSICLE'  => 'gifsicle_bin',
        'PS_IMAGE_OPTIMIZER_BIN_JPEGOPTIM' => 'jpegoptim_bin',
        'PS_IMAGE_OPTIMIZER_BIN_JPEGTRAN'  => 'jpegtran_bin',
        'PS_IMAGE_OPTIMIZER_BIN_OPTIPNG'   => 'optipng_bin',
        'PS_IMAGE_OPTIMIZER_BIN_PNGCRUSH'  => 'pngcrush_bin',
        'PS_IMAGE_OPTIMIZER_BIN_PNGOUT'    => 'pngout_bin',
        'PS_IMAGE_OPTIMIZER_BIN_PNGQUANT'  => 'pngquant_bin',
        'PS_IMAGE_OPTIMIZER_BIN_SVGO'      => 'svgo_bin',
    ];

    /**
     * @var array
     */
    protected $validContentTypes = [
        'image/gif',
        'image/jpeg',
        'image/png',
    ];

    public function __construct()
    {
        add_filter('wp_handle_upload_prefilter', [$this, 'filter_wp_handle_sideload_prefilter'], 4);
        add_filter('wp_handle_sideload_prefilter', [$this, 'filter_wp_handle_sideload_prefilter'], 4);
    }

    /**
     * Get optimizer options
     * 
     * @return array
     */
    protected function get_optimizer_options()
    {
        $options = [
            'ignore_errors' => false,
            'jpegoptim_options' => ['--strip-all', '--all-progressive', '--max=75'],
            'pngquant_options' => ['--force'],
        ];

        foreach ($this->optimizerOptions as $key => $option) {
            if (defined($key)) {
                $options[$option] = constant($key);
            }
        }

        return apply_filters('ps_image_optimizer_options', $options);
    }

    /**
     * @return Optimizer
     */
    private function get_optimizer()
    {
        if (!$this->optimizer) {
            $options = $this->get_optimizer_options();
            $optimizer = new OptimizerFactory($options);
            $this->optimizer = $optimizer->get();
        }

        return $this->optimizer;
    }

    /**
     * Compress uploaded images
     */
    public function filter_wp_handle_sideload_prefilter(array $file)
    {
        // Check file type
        if (!isset($file['type']) || !in_array($file['type'], $this->validContentTypes)) {
            return $file;
        }

        // Optimize image
        if (isset($file['tmp_name'])) {
            try {
                $this->get_optimizer()->optimize($file['tmp_name']);

                $stat = stat($file['tmp_name']);
                if (isset($stat['size'])) {
                    $file['size'] = $stat['size'];
                }
            } catch (\Exception $e) {
                // print_r($e);
            }
        }

        return $file;
    }
}

new OptimizeImages();
