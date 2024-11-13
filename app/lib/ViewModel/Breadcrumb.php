<?php

namespace Redseanet\Lib\ViewModel;

class Breadcrumb extends Template
{
    protected $crumbs = [];
    protected $additional = [];
    protected $showLabel = false;
    protected $showHome = false;

    public function __construct()
    {
        $this->setTemplate('page/breadcrumb');
    }

    /**
     * Get/Set whether the label of the navigation shown or not
     *
     * @param bool $flag
     * @return bool
     */
    public function showLabel($flag = null)
    {
        if (is_bool($flag)) {
            $this->showLabel = $flag;
        }
        return $this->showLabel;
    }

    /**
     * Get/Set whether the home link shown or not
     *
     * @param bool $flag
     * @return bool
     */
    public function showHome($flag = null)
    {
        if (is_bool($flag)) {
            $this->showHome = $flag;
        }
        return $this->showHome;
    }

    /**
     * Get all the crumbs
     *
     * @return array
     */
    public function getCrumbs()
    {
        return $this->crumbs;
    }

    /**
     * Set all the crumbs
     *
     * @param array $crumbs
     * @return Breadcrumb
     */
    public function setCrumbs(array $crumbs)
    {
        $this->crumbs = $crumbs;
        return $this;
    }

    /**
     * Add a crumb to the list
     *
     * @param array $crumbs
     * @return Breadcrumb
     */
    public function addCrumb(array $crumbs)
    {
        $this->crumbs[] = $crumbs;
        return $this;
    }

    /**
     * Get additional links
     *
     * @return array
     */
    public function getAdditional()
    {
        return $this->additional;
    }

    /**
     * Set additional links
     *
     * @param array $additional
     * @return Breadcrumb
     */
    public function setAdditional(array $additional)
    {
        $this->additional = $additional;
        return $this;
    }

    /**
     * Add additional link to the list
     *
     * @param array $additional
     * @return \Redseanet\Lib\ViewModel\Breadcrumb
     */
    public function addAdditional(array $additional)
    {
        $this->additional[] = $additional;
        return $this;
    }
}
