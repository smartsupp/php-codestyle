<?php

namespace Smartsupp\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\FunctionSpacingSniff;
use PHP_CodeSniffer\Files\File;

/**
 * Rules:
 * - Method should have X empty line(s) after itself.
 *
 * Exceptions:
 * - Method is the first in the class, preceded by open bracket.
 * - Method is the last in the class, followed by close bracket.
 * - Method is used inside interface.
 */
final class BetweenMethodSpacingSniff extends FunctionSpacingSniff
{

	/** @var int */
	public $blankLinesBetweenMethods = 2;

	/** @var int */
	private $position;

	/** @var array */
	private $tokens;

	/** @var File */
	private $file;


	/**
	 * {@inheritdoc}
	 */
	public function register()
	{
		return [T_FUNCTION];
	}


	/**
	 * {@inheritdoc}
	 */
	public function process(File $file, $position)
	{
		// Fix type
		$this->blankLinesBetweenMethods = (int) $this->blankLinesBetweenMethods;

		$this->file = $file;
		$this->position = $position;
		$this->tokens = $file->getTokens();

		if ($this->isFileInterface()) {
			return;
		}
		[$blankLinesCountAfterFunction, $stackPtr] = $this->getBlankLineCountAfterFunction();
		if ($blankLinesCountAfterFunction !== $this->blankLinesBetweenMethods) {
			if (!$this->isLastMethod()) {
				$error = 'Method should have %s empty line(s) after itself, %s found.';
				$data = [$this->blankLinesBetweenMethods, $blankLinesCountAfterFunction];
				$shouldFix = $this->file->addFixableError($error, $position, '', $data);
				if ($shouldFix) {
					$file->fixer->beginChangeset();
					if ($blankLinesCountAfterFunction > $this->blankLinesBetweenMethods) {
						for ($i = 0; $i < $blankLinesCountAfterFunction - $this->blankLinesBetweenMethods; ++$i) {
							$file->fixer->replaceToken($stackPtr, '');
						}
					} elseif ($blankLinesCountAfterFunction < $this->blankLinesBetweenMethods) {
						for ($i = 0; $i < $this->blankLinesBetweenMethods - $blankLinesCountAfterFunction; ++$i) {
							$file->fixer->addNewline($stackPtr);
						}
					}
					$file->fixer->endChangeset();
				}
			}
		}
	}


	/**
	 * @return array{0: int, 1: int} Returns tuple [number of lines, last endline token position]
	 * The last token position can be appended to if extra lines are missing, or removed if extra lines exist
	 */
	private function getBlankLineCountAfterFunction(): array
	{
		$closer = $this->getScopeCloser();
		$nextLineToken = $this->getNextLineTokenByScopeCloser($closer);

		$nextContent = $this->getNextLineContent($nextLineToken);
		if ($nextContent !== FALSE) {
			$foundLines = ($this->tokens[$nextContent]['line'] - $this->tokens[$nextLineToken]['line']);

		} else {
			// We are at the end of the file.
			$foundLines = $this->blankLinesBetweenMethods;
		}

		return [$foundLines, ($nextContent ?: $nextLineToken) - 2];
	}


	private function isFileInterface()
	{
		return $this->file->findNext(T_INTERFACE, 0, NULL) !== false;
	}


	private function isLastMethod()
	{
		$closer = $this->getScopeCloser();
		$nextLineToken = $this->getNextLineTokenByScopeCloser($closer);
		if ($this->tokens[$nextLineToken + 1]['code'] === T_CLOSE_CURLY_BRACKET) {
			return TRUE;
		}
		return FALSE;
	}


	/**
	 * @return bool|int
	 */
	private function getScopeCloser()
	{
		if (isset($this->tokens[$this->position]['scope_closer']) === FALSE) {
			// Must be an interface method, so the closer is the semi-colon.
			return $this->file->findNext(T_SEMICOLON, $this->position);
		}

		return $this->tokens[$this->position]['scope_closer'];
	}


	/**
	 * @return int|NULL
	 */
	private function getNextLineTokenByScopeCloser($closer)
	{
		$nextLineToken = NULL;
		for ($i = $closer; $i < $this->file->numTokens; $i++) {
			if (strpos($this->tokens[$i]['content'], $this->file->eolChar) === FALSE) {
				continue;

			} else {
				$nextLineToken = ($i + 1);
				if ( ! isset($this->tokens[$nextLineToken])) {
					$nextLineToken = NULL;
				}

				break;
			}
		}
		return $nextLineToken;
	}


	/**
	 * @return bool|int
	 */
	private function getNextLineContent($nextLineToken)
	{
		if ($nextLineToken !== NULL) {
			return $this->file->findNext(T_WHITESPACE, ($nextLineToken + 1), NULL, TRUE);
		}
		return FALSE;
	}

}
