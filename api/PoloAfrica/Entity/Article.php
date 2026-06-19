<?php

namespace PoloAfrica\Entity;

use \Ninja\Composite\Leaf;

class Article extends Leaf
{
    public $id;
    public $title;
    public $summary;
    public $attr_id;
    public $page;
    public $content;
    public $mdcontent;
    public $pubdate;
    public $assets = [];

    private $image = array(
        '.gif',
        '.jpg',
        '.jpeg',
        '.pjpeg',
        '.png',
        '.x-png'
    );
    private $video = array(
        '.mp4',
        '.avi'
    );

    public function __construct(private \Ninja\DatabaseTable $table, private \Ninja\DatabaseTable $assetTable, private \Ninja\DatabaseTable $slotTable, private $assetlimit) {}

    protected function fetch($table, $prop, $val, ...$rest)
    {
        $ret = [];
        if ($val) { //safeguard against missing values
            $ret = $this->{$table}->find($prop, $val, ...$rest);
        }
        return empty($ret) ? null : $ret[0];
    }

    public function find($table, $prop, $val, ...$rest)
    {

        if ($val) { //safeguard against missing values
            return $this->{$table}->find($prop, $val, ...$rest);
        }
    }

    private function getAssetLimit($orientation)
    {
        $sec = preg_match('/\.section/', $this->attr_id);
        $html = preg_match('/^\w+\.html\.php$/', $this->content);
        return ($sec || $orientation === 'portrait') ? 1 : ($html ? 0 : $this->assetlimit);
    }

    public function validateInsert($orientation)
    {
        return $this->getAssetLimit($orientation);
    }

    protected function persist($record, $route = 'edit')
    {
        $unset = doSetCookie(false);
        $set = doSetCookie(true);
        try {
            $unset('error');
            $e = $this->assetTable->save($record);
        } catch (\Exception $e) {
            $msg = 'Error saving record: ' . $e->getMessage();
            $set('error', $msg);
            reLocate(ASSET_EDIT . $record['id'] ?? '');
        }
        return $e;
    }
    private function validatepath($needle, $haystack)
    {
        return in_array(strrchr($needle, '.'), $haystack);
    }

    public function getAssets($article_id = 0, $cb = null, $prop = null)
    {
        $assetids = array_map(fn($article) => $article->id, $this->assetTable->find('article_id', $article_id));
        $myassets = [];
        foreach ($assetids as $id) {
            $myassets[] = $this->fetch('assetTable', 'id', $id);
        }
        //by default filter out pdf's which are for reference not display
        if (!$cb) {
            $cb =  fn($item) => $this->validatepath($item->path, $this->image) || $this->validatepath($item->path, $this->video);
        }
        $ret = array_values(array_filter($myassets, $cb));
        if ($prop) {
            $ret = array_map(fn($o) => $o->{$prop}, $ret);
        }
        return $ret;
    }

    public function getAsset($t, $k, $v)
    {
        return $this->fetch($t, $k, $v);
    }

    public function setAsset($article_id, $cb, $prop = 'id')
    {
        $assetids = array_map(fn($article) => $article->id, $this->assetTable->find('article_id', $article_id));
        $myassets = [];
        foreach ($assetids as $id) {
            $myassets[] = $this->fetch('assetTable', 'id', $id);
        }
        $items = toObject($cb($myassets), true);
        $neu = [];
        foreach ($items as $item) {
            $neu[] = toObject($this->fetch('assetTable', 'id', $item['id']), true);
        }
        $i = 0;
        $hold = [];
        while (isset($items[$i])) {
            if (!$prop) {
                $hold[] = $items[$i];
            } else if ($prop && ($items[$i][$prop] !== $neu[$i][$prop])) {
                $hold[] = $items[$i];
            }
            $i++;
        }
        $i = 0;
        while (isset($hold[$i])) {
            $hold[$i]['date'] = $prop === 'path' ? date('Y-m-d') : $hold[$i]['date'];
            $this->persist($hold[$i]);
            $i++;
        }
    }

    public function save($record)
    {
        return $this->persist($record);
    }

    public function archiveAssets($article_id, $cb = null)
    {
        $assetIds = array_map(fn($o) => $o->id, $this->getAssets($article_id, $cb));
        foreach ($assetIds as $id) {
            $record = $this->assetTable->find('id', $id, null, 0, 0, \PDO::FETCH_ASSOC);
            if (!empty($record[0])) {
                $record = $record[0];
                $record['article_id'] = NULL;
                $this->persist($record);
            }
        }
    }

    public function moveAssets($article_id, $page)
    {
        $assets = $this->assetTable->find('article_id', $article_id);
        foreach ($assets as $asset) {
            $asset->getStatus($page);
        }
    }

    public function delete($id, $cb = null)
    {
        $assets = $this->getAssets($id, $cb);
        $paths = [];
        foreach ($assets as $asset) {
            $paths[] = $asset->path;
            $this->assetTable->delete('id', $asset->id);
        }
        return $paths;
    }

    public function isSingleAsset($article_id)
    {
        $assets = $this->assetTable->find('article_id', $article_id, null, 0, 0, \PDO::FETCH_ASSOC);
        $multi = count($assets) > 1;
        return $multi ? null : $assets[0]['id'] ?? null;
    }

    public function getArchived()
    {
        return $this->assetTable->find('article_id', null, 'path', 0, 0, \PDO::FETCH_ASSOC, ' IS NULL');
    }

    public function getOddEven($title)
    {
        $record = $this->slotTable->find('title', $title);
        if (isset($record)) {
            return  $record[0]->id % 2 ? 'odd' : 'even';
        }
        return '';
    }
    //articles
    public function isLeadingArticle($page, $title, $section)
    {
        $this->slotTable->setName($page);
        $records = $this->slotTable->findAll();
        $ids = [];
        $id = 0;
        foreach ($records as $r) {
            if (in_array($r->title, $section)) {
                if ($r->title === $title) {
                    $id = $r->id;
                }
                $ids[] = $r->id;
            }
        }
        return $id === min($ids);
    }

    public function getSlotEntity()
    {
        return $this->slotTable->getEntity();
    }

    public function findAll(...$args)
    {
        return $this->slotTable->findAll(...$args);
    }

    public function setName($name)
    {
        //guard against null
        return $this->slotTable->setName(strtolower($name ?? ''));
    }

    public function getName()
    {
        return $this->slotTable->getName();
    }

    public function repop(...$args)
    {
        $slot = $this->getSlotEntity();
        return $slot->repop(...$args);
    }
    public function swap(...$args)
    {
        $slot = $this->getSlotEntity();
        return $slot->swap(...$args);
    }

    public function shuffle(...$args)
    {
        $slot = $this->getSlotEntity();
        return $slot->shuffle(...$args);
    }

    public function trigger(...$args)
    {
        $slot = $this->getSlotEntity();
        return $slot->trigger(...$args);
    }
}
