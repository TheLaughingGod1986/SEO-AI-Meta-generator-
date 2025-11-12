<?php
/**
 * Loader class responsible for registering hooks.
 *
 * @package SeoAiMeta\Core
 */

declare( strict_types=1 );

namespace SeoAiMeta\Core;

/**
 * Handles WordPress hook registration.
 */
final class Loader {

	/**
	 * Collected filters.
	 *
	 * @var array<int, array{hook:string, component:object|string, callback:string, priority:int, accepted_args:int}>
	 */
	private array $filters = array();

	/**
	 * Collected actions.
	 *
	 * @var array<int, array{hook:string, component:object|string, callback:string, priority:int, accepted_args:int}>
	 */
	private array $actions = array();

	/**
	 * Add a filter hook.
	 *
	 * @param string          $hook          Hook name.
	 * @param object|string   $component     Component or class.
	 * @param string          $callback      Callback method.
	 * @param int             $priority      Priority.
	 * @param int             $accepted_args Accepted args.
	 * @return void
	 */
	public function add_filter( string $hook, $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->filters[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
	}

	/**
	 * Add an action hook.
	 *
	 * @param string          $hook          Hook name.
	 * @param object|string   $component     Component or class.
	 * @param string          $callback      Callback method.
	 * @param int             $priority      Priority.
	 * @param int             $accepted_args Accepted args.
	 * @return void
	 */
	public function add_action( string $hook, $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->actions[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
	}

	/**
	 * Register the hooks with WordPress.
	 *
	 * @return void
	 */
	public function run(): void {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}
