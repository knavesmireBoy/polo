<?php

namespace PoloAfrica\Entity;

class Gallery
{
    public $id;
    public $path;
    public $alt;
    public $date;
    public $orient;
    public $box;

    public function __construct(private \Ninja\DatabaseTable $table) {}


    private function getLocation($id)
    {
        $toObject = curry2('toObject')(true);
        $places = $this->table->findAll('id');
        $places = array_map($toObject, $places);
        $picids = array_column($places, 'pic_id');
        //$picids = composer(curry2('array_column')('pic_id'), partial('array_map', $toObject), partial($this->table, 'findAll'))();
        return [array_search($id, $picids) + 1, $places];
    }

    protected function persist($record, $route = 'edit')
    {
        $unset = doSetCookie(false);
        $set = doSetCookie(true);
        try {
            $unset('error');
            $e = $this->table->save($record);
        } catch (\Exception $e) {
            $msg = 'Error saving record: ' . $e->getMessage();
            $set('error', $msg);
            reLocate(GAL_EDIT . $record['id'] ?? '');
        }
        return $e;
    }

    public function reAssign($slotid, $orient, $shuffle = false)
    {
        if (!$slotid) {
            return;
        }
        $destinationID = intval($slotid);
        $current = $this->getSlot(true); //pic is assigned to a slot
        if (empty($current) || ($current->id === $destinationID)) {
            $this->persist(['id' => $destinationID, 'orient' => $orient, 'pic_id' => $this->id]);
            return;
        }
        $checkOrient = doMatch('orient', $current->orient);
        $drive = driver($checkOrient); //strategy, validator
        //note $current->id and $destinationID are from the db and the form respectively
        //$locationID is the position in the array, see $destinationID below;
        //default to swap if no changes
        $shuffle = $shuffle && $current->id !== $destinationID;
        list($locationID, $places) = $this->getLocation($this->id);
        list($otherId, $places) = $this->getLocation($destinationID);
        if (!$shuffle) {
            $this->persist(['id' => $destinationID, 'pic_id' => $this->id]);
            $this->persist(['id' => $locationID, 'pic_id' => $otherId]);
        }
        /*
        if we DON'T decrement $destinationID
        we need minus 1 on the NON $rev invocation:
        $drive(false, $van, $places, $destinationID - 1);
        but then we wouldn't need - 1 for the $rev invocation
        $destinationID = count($places) - $destinationID - 1;
        */ else {
            $rev = $current->id < $destinationID;
            $destinationID--;
            $locationID--;
            $van = [$places[$locationID]];
            $places[$locationID] = null; //must be null or NULL?
            if ($rev) {
                $places = array_reverse($places);
                $destinationID = count($places) - $destinationID - 1;
                $drive(false, $van, $places, $destinationID);
                $places = array_reverse($places);
            } else {
                $drive(false, $van, $places, $destinationID);
            }
            $l = count($places);
            $picids = array_column($places, 'pic_id');
            for ($i = 0; $i  < $l; $i++) {
                $this->persist(['id' => $i + 1, 'pic_id' => $picids[$i] ?? null]);
            }
        }
    }
    //instead of joining tables
    public function orderById($data)
    {
        $L = count($data);
        $addr = [];
        $tmp = [];
        foreach ($data as $d) {
            $found = $this->table->find('pic_id', $d->id);
            $tmp['id'] = $d->id; //the template expects id to be the id of pic NOT box column
            $tmp['alt'] = $d->alt;
            $tmp['path'] = $d->path;
            if (!empty($found)) {
                $tmp['sorter'] = $found[0]->id;
            } else {
                $tmp['sorter'] = $L++;
            }
            $addr[] = (object) $tmp;
            $tmp = [];
        }
        //https://stackoverflow.com/questions/1597736/sort-an-array-of-associative-arrays-by-column-value
        $x = array_column($addr, 'sorter');
        array_multisort($x, SORT_ASC, $addr);
        return $addr;
    }
    public function getCurrent($id)
    {
        $ret = $this->table->find('id', $id, null, 1, 0);
        if (!isset($ret[0])) {
            $ret = $this->table->find('id', 1);
        }
        return $ret[0]->pic_id;
    }

    public function getNext($id)
    {
        $ret = $this->table->find('id', $id, null, 1, 0, \PDO::FETCH_CLASS, ' > :value');
        if (!isset($ret[0])) {
            $ret = $this->table->find('id', 1);
        }
        return $ret[0]->pic_id;
    }

    public function getPrev($id)
    {
        //find all less than
        $ret = $this->table->find('id', $id, null, 0, 0, \PDO::FETCH_ASSOC, ' < :value');
        if (!isset($ret[0])) {
            $ret = $this->table->find('id', count($this->table->findAll()));
        } else {
            $ret = array_reverse($ret)[0];
            $ret = $this->table->find('id', $ret['id']);
        }
        return $ret[0]->pic_id;
    }

    public function getSlot($flag = false)
    {
        $ret = $this->table->find('pic_id', $this->id);
        $flag = $flag && isset($ret[0]);
        return $flag ? $ret[0] : null;
    }

    public function getStatus($flag = false)
    {
        $ids = array_map(fn($o) => $o->pic_id, $this->table->findAll());
        return $flag ? in_array($this->id, $ids) : !in_array($this->id, $ids);
    }

    public function getuntracked($records)
    {
        $ids = array_map(fn($o) => $o->pic_id, $this->table->findAll());
        $cb = function ($o) use ($ids) {
            return in_array($o->id, $ids);
        };
        // $active = safeFilter($records, $cb);
        $active = array_map(fn($o) => $o->path, $records);
        $scanned = safeScanDir(GALLERY_IMG);
        return arrayDiff($scanned, $active);
    }
}
