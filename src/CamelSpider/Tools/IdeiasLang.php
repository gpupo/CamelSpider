<?php

namespace CamelSpider\Tools;

/**
 * @package IdeiasLang
 * @author Ricardo Striquer Soares ricardo@ideiaspontual.com
 * @version 1.0
 * @link www.ideiaspontual.com
 * @copyright 2004, Ideias Pontual Desenvolvimento de Software Ltda.
 */
class IdeiasLang 
{
    /**
	 * Transforma todo tipo de quebra de linha em um mesmo caracter
	 * @param string $sText Texto a ser analisado
	 * @param string $sLF Eh o caractere ou sequencia de caracteres que deve ser
	 * utilizado para a quebra de linha.
	 * @return string Texto adicionado da quebra de linha.
	 */
	function sameLF(&$sText, $sLF="\n") {

		$sText = str_replace("\r\n", "\n", $sText);
		$sText = str_replace("\r", "\n", $sText);

		if ($sLF!="\n") {
			$sText = str_replace("\n", $sLF, $sText);
		}

		return($sText);
	}
    /**
	 * Retorna o percentual de diferenca entre dois os arquivos.
	 * 
	 * @param string $sFile1 Conteudo original, se for passado um valor NULL a
	 * funcao retorna um valor negativo.
	 * @param string $sFile2 Conteudo a ser comparado, se for passado um valor
	 * NULL a funcao retorna um valor negativo.
	 * 
	 * @return double Quantidade de diferenca entre o arquivo 1 e 2, se for um
	 * valor negativo quer dizer que um dos dois arquivos estava vazio e por
	 * isto nao houve comparacao.
	 */
	function iDiff(&$sFile1, &$sFile2) {
		// resultado da funcao a ser retornado
		$dRst = (double) 0;
		// array de palavras do arquivo 1
		$aWords1 = (array) NULL;
		// Contagem de palavras no aruqivo 1
		$iCount1 = (integer) 0;
		// array de palavras do arquivo 2
		$aWords2 = (array) NULL;
		// contagem de palavras do arquivo 2
		$iCount2 = (integer) 0;
		// Valor da diferenca entre um arquivo e outro.
		$iDiff = (integer) 0;

		if ($sFile1==NULL && $sFile2!=NULL || $sFile1!=NULL && $sFile2==NULL) {
			$dRst = (double) -1;

		} else {
			// Seleciona, separa em array e conta os dados atualmente no banco
			$aWords1 = explode(' ', self::sameLF($sFile1, ' ') );
			$iCount1 = (integer) count($aWords1);

			// Separa em array e conta os dados atualmente na URL
			$aWords2 = explode(' ', self::sameLF($sFile2, ' ') );
			$iCount2 = count($aWords2);

			// lower case them all
			for ($i=0; $i<$iCount1; $i++) {
				$aWords1[$i] = strtolower($aWords1[$i]);
			}
			for ($i=0; $i<$iCount2; $i++) {
				$aWords2[$i] = strtolower($aWords2[$i]);
			}

			// - Pega a diferenca de quantidade de palavras de uma varredura para
			// outra.
			if ($iCount1 - $iCount2 > 1) {
				foreach ($aWords2 AS $sVal) {
					$bAchou = array_search($sVal, $aWords1);
					if ( $bAchou == NULL || $bAchou = false) {
						$iDiff++;
					}
				}
			} else {
				foreach ($aWords1 AS $sVal) {
					$bAchou = array_search($sVal, $aWords2);
					if ( $bAchou == NULL || $bAchou = false) {
						$iDiff++;
					}
				}
			}

			// Calcula a porcentagem de diferença
			$dRst = (double) floor( ($iDiff*100) / $iCount1 );
		}
		
		if ($dRst>100) {
			 // - eh possivel (inprovavel) que haja um valor mair posto que
			 // iCount1 pode ser menor que iCount2 e todas as suas palavras
			 // sejam diferentes 
			
			$bRst = 100;
		}

		return ($dRst);
	}
}

