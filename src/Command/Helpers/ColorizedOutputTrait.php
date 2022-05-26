<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

/**
 * Puts the text into the appropriate tag to colorise.
 */
trait ColorizedOutputTrait {

  /**
   * The options used in the formatted text.
   *
   * @var string
   */
  protected $options = 'options=bold';

  /**
   * Sets the output for the outputted text.
   *
   * @param string $options
   *   Set the options for the output.
   */
  public function setOutputOptions(string $options) {
    $this->options = $options;
  }

  /**
   * Returns a text within the comment.
   *
   * @param string $text
   *   The text to format.
   *
   * @return string
   *   The formatted text.
   */
  public function comment(string $text): string {
    return sprintf('<comment>%s</comment>', $text);
  }

  /**
   * Returns a text within the info.
   *
   * @param string $text
   *   The text to format.
   *
   * @return string
   *   The formatted text.
   */
  public function info(string $text): string {
    return sprintf('<info>%s</info>', dt($text));
  }

  /**
   * Returns a text within the error.
   *
   * @param string $text
   *   The text to format.
   *
   * @return string
   *   The formatted text.
   */
  public function error(string $text): string {
    return sprintf('<error>%s</error>', $text);
  }

  /**
   * Returns a red text.
   *
   * @param string $text
   *   The text to format.
   *
   * @return string
   *   The formatted text.
   */
  public function toRed(string $text): string {
    return $this->fmtString($text, 'red');
  }

  /**
   * Returns a green text.
   *
   * @param string $text
   *   The text to format.
   *
   * @return string
   *   The formatted text.
   */
  public function toGreen(string $text): string {
    return $this->fmtString($text, 'green');
  }

  /**
   * Returns a yellow text.
   *
   * @param string $text
   *   The text to format.
   *
   * @return string
   *   The formatted text.
   */
  public function toYellow(string $text): string {
    return $this->fmtString($text, 'yellow');
  }

  /**
   * Returns the text in the given color within the appropriate tags.
   *
   * @param string $text
   *   The text to format.
   * @param string $color
   *   The color to return the text in.
   *
   * @return string
   *   The formatted text.
   */
  public function fmtString(string $text, string $color): string {
    return sprintf('<fg=%s;%s>%s</>', $color, $this->options, $text);
  }

}
