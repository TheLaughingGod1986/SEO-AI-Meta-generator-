<?php
/**
 * Legacy plugin class compatibility.
 *
 * @package SEO_AI_Meta
 */

if ( ! class_exists( 'SEO_AI_Meta', false ) ) {
	class SEO_AI_Meta extends \SeoAiMeta\Core\Plugin {
	}
}
