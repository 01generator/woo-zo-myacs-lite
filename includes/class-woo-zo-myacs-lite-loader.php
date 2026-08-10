<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Collect and register WordPress actions and filters for the plugin.
 */
class Woo_Zo_Myacs_Lite_Loader
{
    protected $actions = array();
    protected $filters = array();

    /**
     * Queue an action hook for registration during run().
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->actions[] = compact('hook', 'component', 'callback', 'priority', 'accepted_args');
    }

    /**
     * Queue a filter hook for registration during run().
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->filters[] = compact('hook', 'component', 'callback', 'priority', 'accepted_args');
    }

    /**
     * Register all queued filters and actions with WordPress.
     */
    public function run()
    {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}
