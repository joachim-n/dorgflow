<?php

namespace Dorgflow\Console;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

/**
 * Console helper to output a bulletted list.
 *
 * (Uses ugly AF formatting in case I submit this as a PR to Symfony.)
 */
class ItemList extends ListBase {

  /**
   * Adds an item to the list.
   *
   * @param string|ListBase $item
   *   Either a string for a plain item, or another ListBase object for a nested
   *   list.
   */
  public function addItem($item)
  {
      $this->items[] = $item;

      if ($this->progressive) {
        $this->writeItem($item);
      }

      return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatSingleItem($item) {
    return $item;
  }

}
