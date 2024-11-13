<?php

namespace Redseanet\Admin\ViewModel\Resource;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Resource\Model\Collection\Resource;
use Redseanet\Resource\Model\Collection\Category;
use Redseanet\Admin\Model\User;

class Explorer extends Template
{
    protected static $store = null;
    protected static $tree = null;
    protected static $resource = null;
    protected $type = [
        'application/x-compressed' => 'file-archive',
        'application/x-compress' => 'file-archive',
        'application/x-tar' => 'file-archive',
        'application/x-xz' => 'file-archive',
        'application/x-rar-compressed' => 'file-archive',
        'application/x-gtar' => 'file-archive',
        'application/x-gzip' => 'file-archive',
        'application/x-bzip2' => 'file-archive',
        'application/zip' => 'file-archive',
        'application/octet-stream' => 'file-archive',
        'application/pdf' => 'file-pdf',
        'audio' => 'file-audio',
        'image' => 'file-image',
        'video' => 'file-video',
        'text' => 'file-text'
    ];
    protected $unit = [
        'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB', 'BB', 'NB', 'DB'
    ];

    public function getStore()
    {
        if (is_null(self::$store)) {
            $segment = new Segment('admin');
            $userArray = (new Segment('admin'))->get('user');
            $user = new User();
            $user->load($userArray['id']);
            self::$store = $user->getStore();
        }
        return self::$store;
    }

    public function getCurrentUrl()
    {
        $uri = $this->getRequest()->getUri();
        return $uri->withFragment('')->withQuery('');
    }

    public function getLanguageId()
    {
        return Bootstrap::getLanguage()->getId();
    }

    public function getFileTypes()
    {
        $collection = new Resource();
        $collection->columns(['file_type'])
                ->group(['file_type']);
        if ($this->getStore()) {
            $collection->where(['store_id' => $this->getStore()->getId()]);
        }
        return $collection;
    }

    public function getFileSize($size, $unit = 0)
    {
        return $size > 1024 && isset($this->unit[$unit + 1]) ?
                $this->getFileSize(function_exists('bcdiv') ? bcdiv($size, 1024, 2) : $size / 1024, $unit + 1) :
                sprintf('%.2f%s', $size, $this->unit[$unit]);
    }

    public function getResourceType($type)
    {
        return $this->type[$type] ?? $this->type[substr($type, 0, strpos($type, '/'))] ?? 'file';
    }

    public function getResources()
    {
        if (is_null(self::$resource)) {
            $data = $this->getQuery();
            $limit = $data['limit'] ?? 20;
            self::$resource = new Resource();
            self::$resource->where(['category_id' => empty($data['category_id']) ? null : $data['category_id']])->limit((int) $limit);
            if ($this->getStore()) {
                self::$resource->where(['store_id' => $this->getStore()->getId()]);
            }
            if (isset($data['desc'])) {
                self::$resource->order($data['desc'] . ' DESC');
            }
            $filter = [];
            if (!empty($data['file_type'])) {
                $filter['file_type'] = $data['file_type'];
            }
            if (!empty($data['uploaded_name'])) {
                $filter['uploaded_name'] = $data['uploaded_name'];
            }
            self::$resource->where($filter)->offset($limit * (empty($data['page']) ? 0 : $data['page'] - 1));
        }
        return self::$resource;
    }

    public function getCategoryTree()
    {
        if (is_null(static::$tree)) {
            $collection = new Category();
            if ($this->getStore()) {
                $collection->where(['store_id' => $this->getStore()->getId()]);
            }
            static::$tree = [];
            foreach ($collection as $category) {
                if (!isset(static::$tree[(int) $category->offsetGet('parent_id')])) {
                    static::$tree[(int) $category->offsetGet('parent_id')] = [];
                }
                static::$tree[(int) $category->offsetGet('parent_id')][] = $category;
            }
        }
        return static::$tree;
    }

    public function getChildrenCategories($id = 0, $title = null)
    {
        $child = new static();
        $child->setTemplate('admin/resource/category')
                ->setVariables([
                    'id' => $id,
                    'title' => is_null($title) ? $this->translate('All Categories') :
                            (is_scalar($title) ? $title : ($title[$this->getLanguageId()] ?? current($title)))
                ]);
        return $child;
    }
}
