<?php

namespace App\Mvc\View;

//use App\Mvc\View;

abstract class AbstractHelper
{
    /**
     * View object
     *
     * @var View
     */
    protected $view = null;

    /**
     * Set the View object
     *
     * @param  View $view
     */
    public function setView($view)
    {
        $this->view = $view;
        return $this;
    }

    /**
     * Get the view object
     *
     * @return View
     */
    public function getView()
    {
        return $this->view;
    }
}
