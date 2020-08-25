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
	public function process(File $file, $position): void
	{
		parent::process($file, $position);
		$this->emptyLinesAfterOpeningBrace = (int) $this->emptyLinesAfterOpeningBrace;
		$this->emptyLinesBeforeClosingBrace = (int) $this->emptyLinesBeforeClosingBrace;

		$this->processOpen($file, $position);
		$this->processClose($file, $position);
	}


	private function processOpen(File $file, int $position): void
	{
		$tokens = $file->getTokens();
		$openingBracePosition = $tokens[$position]['scope_opener'];

		$nextToken = $this->getNextTokenAfterOpeningBrace($file, $openingBracePosition);
		if ($nextToken['code'] === T_CONST || $nextToken['code'] === T_COMMENT || $nextToken['code'] === T_USE) {
			return;
		}

		[$emptyLinesCount, $stackPtr] = $this->getEmptyLinesAfterOpeningBrace($file, $openingBracePosition);
		if ($emptyLinesCount !== $this->emptyLinesAfterOpeningBrace) {
			$error = 'Opening brace for the %s should be followed by %s empty line(s); %s found.';
			$data = [
				$tokens[$position]['content'],
				$this->emptyLinesAfterOpeningBrace,
				$emptyLinesCount,
			];
			$shouldFix = $file->addFixableError($error, $openingBracePosition, 'OpenBraceFollowedByEmptyLines', $data);

			if ($shouldFix) {
				$file->fixer->beginChangeset();
				if ($emptyLinesCount > $this->emptyLinesAfterOpeningBrace) {
					for ($i = 0; $i < $emptyLinesCount - $this->emptyLinesAfterOpeningBrace; ++$i) {
						$file->fixer->replaceToken($stackPtr, '');
					}
				} elseif ($emptyLinesCount < $this->emptyLinesAfterOpeningBrace) {
					for ($i = 0; $i < $this->emptyLinesAfterOpeningBrace; ++$i) {
						$file->fixer->addNewline($stackPtr);
					}
				}
				$file->fixer->endChangeset();
			}
		}
	}


	private function processClose(File $file, int $position): void
	{
		$tokens = $file->getTokens();
		$closeBracePosition = $tokens[$position]['scope_closer'];
		[$emptyLinesCount, $stackPtr] = $this->getEmptyLinesBeforeClosingBrace($file, $closeBracePosition);

		if ($emptyLinesCount !== $this->emptyLinesBeforeClosingBrace) {
			$error = 'Closing brace for the %s should be preceded by %s empty line(s); %s found.';
			$data = [
				$tokens[$position]['content'],
				$this->emptyLinesBeforeClosingBrace,
				$emptyLinesCount
			];
			$shouldFix = $file->addFixableError($error, $closeBracePosition, 'CloseBracePrecededByEmptyLines', $data);

			if ($shouldFix) {
				$file->fixer->beginChangeset();
				if ($emptyLinesCount > $this->emptyLinesBeforeClosingBrace) {
					for ($i = 0; $i < $emptyLinesCount - $this->emptyLinesBeforeClosingBrace; ++$i) {
						$file->fixer->replaceToken($stackPtr, '');
					}
				} elseif ($emptyLinesCount < $this->emptyLinesBeforeClosingBrace) {
					for ($i = 0; $i < $this->emptyLinesBeforeClosingBrace - $emptyLinesCount; ++$i) {
						$file->fixer->addNewline($stackPtr);
					}
				}
				$file->fixer->endChangeset();
			}
		}
	}


	private function getNextTokenAfterOpeningBrace(File $file, ?int $position): array
	{
		$tokens = $file->getTokens();
		$nextContent = $file->findNext(T_WHITESPACE, ($position + 1), NULL, TRUE);
		return $tokens[$nextContent];
	}


	/**
	 * @param File $file
	 * @param int|null $position
	 * @return array{0: int, 1: int} Returns tuple [number of newlines, position of last newline token]
	 */
	private function getEmptyLinesAfterOpeningBrace(File $file, ?int $position): array
	{
		$tokens = $file->getTokens();
		$nextContent = $file->findNext(T_WHITESPACE, ($position + 1), NULL, TRUE);
		return [$tokens[$nextContent]['line'] - $tokens[$position]['line'] - 1, $nextContent - 2];
	}


	/**
	 * @param File $file
	 * @param int|null $position
	 * @return array{0: int, 1: int} Returns tuple [number of newlines, position of last newline token]
	 */
	private function getEmptyLinesBeforeClosingBrace(File $file, ?int $position): array
	{
		$tokens = $file->getTokens();
		$prevContent = $file->findPrevious(T_WHITESPACE, ($position - 1), NULL, TRUE);
		return [$tokens[$position]['line'] - $tokens[$prevContent]['line'] - 1, $prevContent + 1];
	}

}
