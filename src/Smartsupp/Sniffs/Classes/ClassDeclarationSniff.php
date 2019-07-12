<?php

namespace Smartsupp\Sniffs\Classes;

use PHP_CodeSniffer\Standards\PEAR\Sniffs\Classes\ClassDeclarationSniff as PEARClassDeclarationSniff;
use PHP_CodeSniffer\Files\File;

/**
 * Rules (new to parent class):
 * - Opening brace for the %s should be followed by %s empty line(s).
 * - Closing brace for the %s should be preceded by %s empty line(s).
 *
 * Exceptions:
 * - Opening brace can be followed with simple comment without new line.
 * - Opening brace can be followed with constant or use without new line.
 */
final class ClassDeclarationSniff extends PEARClassDeclarationSniff
{

	/** @var int */
	public $emptyLinesAfterOpeningBrace = 1;

	/** @var int */
	public $emptyLinesBeforeClosingBrace = 1;


	/**
	 * {@inheritdoc}
	 */
	public function process(File $file, $position)
	{
		parent::process($file, $position);
		$this->emptyLinesAfterOpeningBrace = (int) $this->emptyLinesAfterOpeningBrace;
		$this->emptyLinesBeforeClosingBrace = (int) $this->emptyLinesBeforeClosingBrace;

		$this->processOpen($file, $position);
		$this->processClose($file, $position);
	}


	private function processOpen(File $file, $position)
	{
		$tokens = $file->getTokens();
		$openingBracePosition = $tokens[$position]['scope_opener'];

		$nextToken = $this->getNextTokenAfterOpeningBrace($file, $openingBracePosition);
		if ($nextToken['code'] === T_CONST || $nextToken['code'] === T_COMMENT || $nextToken['code'] === T_USE) {
			return;
		}

		$emptyLinesCount = $this->getEmptyLinesAfterOpeningBrace($file, $openingBracePosition);
		if ($emptyLinesCount !== $this->emptyLinesAfterOpeningBrace) {
			$error = 'Opening brace for the %s should be followed by %s empty line(s); %s found.';
			$data = [
				$tokens[$position]['content'],
				$this->emptyLinesAfterOpeningBrace,
				$emptyLinesCount,
			];
			$file->addError($error, $openingBracePosition, 'OpenBraceFollowedByEmptyLines', $data);
		}
	}


	private function processClose(File $file, $position)
	{
		$tokens = $file->getTokens();
		$closeBracePosition = $tokens[$position]['scope_closer'];
		$emptyLines = $this->getEmptyLinesBeforeClosingBrace($file, $closeBracePosition);

		if ($emptyLines !== $this->emptyLinesBeforeClosingBrace) {
			$error = 'Closing brace for the %s should be preceded by %s empty line(s); %s found.';
			$data = [
				$tokens[$position]['content'],
				$this->emptyLinesBeforeClosingBrace,
				$emptyLines
			];
			$file->addError($error, $closeBracePosition, 'CloseBracePrecededByEmptyLines', $data);
		}
	}


	private function getNextTokenAfterOpeningBrace(File $file, $position)
	{
		$tokens = $file->getTokens();
		$nextContent = $file->findNext(T_WHITESPACE, ($position + 1), NULL, TRUE);
		return $tokens[$nextContent];
	}


	private function getEmptyLinesAfterOpeningBrace(File $file, $position)
	{
		$tokens = $file->getTokens();
		$nextContent = $file->findNext(T_WHITESPACE, ($position + 1), NULL, TRUE);
		return $tokens[$nextContent]['line'] - $tokens[$position]['line'] - 1;
	}


	private function getEmptyLinesBeforeClosingBrace(File $file, $position)
	{
		$tokens = $file->getTokens();
		$prevContent = $file->findPrevious(T_WHITESPACE, ($position - 1), NULL, TRUE);
		return $tokens[$position]['line'] - $tokens[$prevContent]['line'] - 1;
	}

}
