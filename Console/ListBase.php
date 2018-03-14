<?php

namespace Dorgflow\Console;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

/**
 * Console helper to output a definition list.
 *
 * (Uses ugly AF formatting in case I submit this as a PR to Symfony.)
 */
abstract class ListBase {

  /**
   * List items.
   *
   * @var array
   */
  protected $items = [];

  /**
   * String to use as a bullet. Does not include trailing space.
   *
   * @var string
   */
  protected $bullet = '-';

  /**
   * Indentation level of this list.
   *
   * @var int
   */
  protected $indentLevel = 0;

  /**
   * Whether rendering is progressive.
   *
   * @var bool
   */
  protected $progressive = FALSE;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  protected $output;

  public function __construct(OutputInterface $output)
  {
      $this->output = $output;
  }

  /**
   * Set the list to render items as soon as they are added.
   *
   * If this is set, calling render() will have no effect.
   *
   * @param bool $progressive
   *  (optional) Whether to set the list as progressive.
   */
  public function setProgressive($progressive = TRUE)
  {
    $this->progressive = $progressive;
  }

  /**
   * Sets the bullet for the list.
   *
   * @param string $bullet
   *   The string to use as a bullet for each list item. Do not include any
   *   trailing space or indentation.
   */
  public function setBullet($bullet)
  {
    // Prevent changing of the bullet midway through output.
    // TODO: don't prevent this when output is non-progressive.
    if (!empty($this->items)) {
      throw new \Exception("The bullet may not be changed once output has started.");
    }

    $this->bullet = $bullet;
  }

  /**
   * Sets the indentation level of the list.
   *
   * @param int $indent_level
   *   The indentation level. The list will be indented by double this number
   *   of spaces.
   */
  public function setIndentLevel($indent_level) {
    $this->indentLevel = $indent_level;
  }

  /**
   * Creates a list helper for a nested list.
   *
   * @param string $class
   *   The class to use for the list.
   *
   * @return
   *   The new list helper object.
   */
  public function getNestedListItem($class) {
    // Give the nested list a buffered output, so we can collect its output
    // and indent it in the outer list.
    // The buffered output is given the same parameters as the output the parent
    // list has, so things such as formatting work.
    $buffered_output = new \Symfony\Component\Console\Output\BufferedOutput(
      $this->output->getVerbosity(),
      $this->output->isDecorated(),
      $this->output->getFormatter()
    );

    $nested_list = new $class($buffered_output);

    $nested_list->setIndentLevel($this->indentLevel + 1);

    return $nested_list;
  }

  /**
   * Renders the list.
   *
   * This has no effect if the list is progressive.
   *
   * @return string|null
   *   Returns the output if the output is buffered.
   */
  public function render()
  {
    if (!$this->progressive) {
      foreach ($this->items as $item) {
        $this->writeItem($item);
      }

      // If the output is buffered, fetch the buffer.
      if ($this->output instanceof \Symfony\Component\Console\Output\BufferedOutput) {
        return $this->output->fetch();
      }
    }
  }

  /**
   * Returns the bullet string.
   *
   * @return string
   *   The bullet with a trailing space appended.
   */
  protected function getBulletWithSpacing() {
    if (empty($this->bullet)) {
      $bullet = '';
    }
    else {
      $bullet = $this->bullet . ' ';
    }

    return $this->getNestingIndent() . $bullet;
  }

  /**
   * Returns a blank string of the same length as the bullet string.
   *
   * @return string
   *   A string composed of only spaces, whose length is the same as that
   *   returned by getBulletWithSpacing().
   */
  protected function getBulletIndentString() {
    $bullet_width = strlen($this->bullet);

    if (empty($bullet_width)) {
      return $this->getNestingIndent();
    }

    $bullet_width++;

    return $this->getNestingIndent() . str_repeat(' ', $bullet_width);
  }

  /**
   * Returns a blank string for the indent.
   *
   * @return string
   *   A string composed of only spaces, whose length twice the indent level.
   */
  protected function getNestingIndent() {
    return str_repeat('  ', $this->indentLevel);
  }

  /**
   * Gets the width to wrap lines to, taking the bullet into account.
   *
   * @return int
   *   The number of characters to wrap by.
   */
  protected function getWrapWidth() {
    // The indent consists of the nesting indent + the bullet + the bullet's
    // trailing space.
    $nesting_indent_width = $this->indentLevel * 2;
    $bullet_width = strlen($this->bullet);
    $total_indent_width = $nesting_indent_width + $bullet_width + 1;

    $terminal_width = (new Terminal())->getWidth();
    $line_wrap_width = $terminal_width - $total_indent_width;

    return $line_wrap_width;
  }

  /**
   * Output a single item.
   *
   * @param mixed $item
   *   The item.
   */
  protected function writeItem($item)
  {
    if ($item instanceof ListBase) {
      //$this->output->writeln($this->getBulletWithSpacing() . $item);
      $output = $item->render();

      // Splice a bullet at the front of the nested list.
      $bullet = $this->getBulletWithSpacing();
      $output = substr_replace($output, $bullet, 0, strlen($bullet));

      $this->output->write($output);
    }
    else {
      $formatted_item = $this->formatSingleItem($item);

      // Wrap the line if necessary.
      $line_wrap_width = $this->getWrapWidth();
      if (strlen($formatted_item) > $line_wrap_width) {
        $bullet_indent = $this->getBulletIndentString();
        $formatted_item = wordwrap($formatted_item, $line_wrap_width, "\n$bullet_indent", TRUE);
      }

      $this->output->writeln($this->getBulletWithSpacing() . $formatted_item);
    }
  }

  /**
   * Formats a single item.
   *
   * @var mixed $item
   *   The item.
   *
   * @return string
   *   The formatted item. This does not include the indent, bullet, or spacing
   *   after the bullet.
   */
  abstract protected function formatSingleItem($item);

}
