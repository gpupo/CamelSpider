<?php
/**
 * Ideias Pontual Desenvolvimento de Software Ltda.
 *
 * The contents of this file can not be used for any purpose
 * without the permission of the Ideias Pontual Desenvolvimento de
 * Software Ltda.<BR>
 * You may obtain more information by emailing for
 * vendas@ideiaspontual.com<BR>
 * <BR>
 * Comentarios:<BR>
 * - Aqui um simples comentario.
 *
 * @link http://www.ideiaspontual.com/
 * @since Ago/2003
 * @name scripts/IdeiasLang/iString.class.php
 */

/**
 * Classe de conexao com o banco de dados.
 *
 * - Manipulate system cityes information, any city 
 * information required by the system should be manipulated by 
 * this class.<BR>
 *
 * @package IdeiasLang
 * @author Ricardo Striquer Soares ricardo@ideiaspontual.com
 * @version 1.0
 * @link www.ideiaspontual.com
 * @copyright 2004, Ideias Pontual Desenvolvimento de Software Ltda.
 */
class iFileSystem {
	/**
	 * Creates a pointer to the next file name.
	 */
	function Next($sFileName=NULL) {
		// if 
	}
	// escreve a linha em um arquivo cvs
	function __WriteCSV($fp, $xData) {
	        $iCount = (integer) 0;
	        $iX0 = (integer) 0;
	
	        if (!is_array($xData)) {
	                fwrite($fp, $xData);
	                fwrite($fp, "\r\n");
	        }else {
	                $iCount = (integer) count($xData);
	                for ($iX0=0; $iX0<$iCount; $iX0++) {
	                        fwrite($fp, $xData[$iX0].";");
	                }
	                fwrite($fp, "\r\n");
	        }
	}
	/**
	 * Pega o conteudo de um arquivo e o transforma para ser armazenado na base de dados.
	 * 
	 */
	function getFileContent($sFileName, $bIn=true, $sCRLF = "\n") {
		$sRst = (string) NULL;

		if ($bIn) {
			$sRst = addslashes(implode($sCRLF, file($sFileName)));
		} else {
			$sRst = stripslashes($sFileName);
		}

		return($sRst);
	}
	/**
	 * Pega o diretorio
	 * @param integer $iIndex Se for 0 (zero) quer dizer que eh o diretorio atual
	 *  se for 1 quer dizer que eh o primeiro diretorio na estrutura a partir do principal
	 *  ou seja, o /, se for um numero negativo inicia contando a partir do diretorio atual.
	 * @return string Nome do diretorio indicado. 
	 */
	function getFolder($iIndex = 0) {
		// valor a ser retornado pela funcao.
		$sRst = (string) NULL;
		// buffer para o array
		$aBuf = (array) NULL;
		
		// identifica o diretorio atual
		$sFolder = getcwd();
		
		if (__OS_TYPE__==__OS_WINDOWS__) {
			$sFolder = str_replace('\\', '/', $sFolder);
		}

		$aBuf = explode('/', $sFolder);
		
		// retorna de acordo com o selecionado.
		if ($iIndex==0) {
			$sRst = $aBuf[count($aBuf)-1];
		} elseif($iIndex>1) {
			$sRst = $aBuf[$iIndex];
		} elseif($iIndex<1) {
			$iIndex--;
			$sRst = $aBuf[count($aBuf) + $iIndex];
		}

		return($sRst);
	}
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
			$aWords1 = explode(' ', iFileSystem :: sameLF($sFile1, ' ') );
			$iCount1 = (integer) count($aWords1);

			// Separa em array e conta os dados atualmente na URL
			$aWords2 = explode(' ', iFileSystem :: sameLF($sFile2, ' ') );
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

?>
