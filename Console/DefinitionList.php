<?php

namespace Dorgflow\Console;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

/**
 * Console helper to output a list.
 *
 * (Uses ugly AF formatting in case I submit this as a PR to Symfony.)
 */
class DefinitionList extends ListBase {

  /**
   * {@inheritdoc}
   */
  protected $bullet = '';

  /**
   * Whether the list uses a formatter style.
   *
   * @var bool
   */
  protected $usesDefinitionStyle = FALSE;

  /**
   * Sets the formatter style for the list terms.
   *
   * @param \Symfony\Component\Console\Formatter\OutputFormatterStyle $style
   *   The style object.
   */
  public function setDefinitionFormatterStyle(OutputFormatterStyle $style)
  {
      $this->output->getFormatter()->setStyle('definition', $style);
      $this->usesDefinitionStyle = TRUE;
  }

  /**
   * Adds an item to the list.
   *
   * TODO: support a nested list.
   *
   * @param string $definition
   *   The definition.
   * @param string $term
   *   The term.
   */
  public function addItem($definition, $term)
  {
      $item = [$definition, $term];

      $this->items[] = $item;

      if ($this->progressive) {
          $this->writeItem($item);
      }

      return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatSingleItem($item)
  {
      list($definition, $term) = $item;

      if ($this->usesDefinitionStyle) {
          return "<definition>{$definition}</>" . ': ' . $term;
      }
      else {
          return $definition . ': ' . $term;
      }
  }

}
