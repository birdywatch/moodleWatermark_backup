<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Strings for component 'moodlewatermark', language 'pt_pt'
 *
 * @package    mod_moodlewatermark
 * @copyright 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['cannotgeneratewatermark'] = 'Não foi possível gerar a marca d\'água';
$string['clicktodownload'] = 'Clique no link {$a} para descarregar o ficheiro.';
$string['clicktoopen2'] = 'Clique no link {$a} para visualizar o ficheiro.';
$string['configdisplayoptions'] = 'Selecione todas as opções que devem estar disponíveis, as configurações existentes não são modificadas. Segurar CTRL irá permitir selecionar vários campos.';
$string['configframesize'] = 'Quando uma página web ou um ficheiro carregado é exibido dentro de um quadro, este valor é a altura (em pixels) do quadro superior (que contém a navegação).';
$string['configpopupheight'] = 'Qual deve ser a altura padrão para novas janelas pop-up?';
$string['displayoptions'] = 'Opções de exibição disponíveis';
$string['displayselect'] = 'Exibição';
$string['displayselect_help'] = 'Essa configuração, junto com o tipo de ficheiro e se o navegador permitir a incorporação, determina como o ficheiro é exibido. As opções podem incluir:
* Automático - A melhor opção de exibição para o tipo de ficheiro é selecionada automaticamente
* Incorporar - O ficheiro é exibido na página abaixo da barra de navegação junto com a descrição do ficheiro e quaisquer blocos
* Forçar download - O usuário é solicitado a descarregar o ficheiro
* Abrir - apenas o ficheiro é exibido na janela do navegador
* Em pop-up - O ficheiro é exibido em uma nova janela do navegador sem menus ou barra de endereço
* In frame - O ficheiro é exibido dentro de um frame abaixo da barra de navegação e da descrição do ficheiro';
$string['displayselectexplain'] = 'Escolha o tipo de exibição; infelizmente, nem todos os tipos são adequados para todos os ficheiros.';
$string['description'] = "Descrição";
$string['filenotfound'] = 'ficheiro não encontrado, pedimos desculpa pelo incomodo.';
$string['filterfiles'] = 'Utilize filtros no conteúdo do ficheiro';
$string['filterfilesexplain'] = 'Selecione o tipo de filtragem de conteúdo de ficheiro. Observe que isso pode causar problemas para alguns miniaplicativos Flash e Java. Certifique-se de que todos os ficheiros de texto estão em codificação UTF-8.';
$string['framesize'] = 'Altura do quadro';

/** Module info */
$string['modifieddate'] = 'Modificado {$a}';
$string['modulename'] = 'ficheiro com marca d\'água';
$string['modulename_help'] = 'O modulo de ficheiro com marca d\'água permite ao professor prover um ficheiro que conterá a marca d\'água com os dados do usuário como um recurso ao curso. Os estudantes poderão fazer o download do ficheiro. O ficheiro deve ter extensão PDF.';
$string['modulenameplural'] = 'ficheiros com marca d\'água';
$string['name'] = "Nome";
$string['notmigrated'] = 'Este tipo de recurso legado ({$a}) ainda não foi migrado, pedimos desculpa pelo incomodo.';
$string['pluginadministration'] = 'Administração módulo de ficheiro com marca d\'água';
$string['pluginname'] = "ficheiro com marca d\'água";
$string['popupheight'] = 'Altura do pop-up (em pixels)';
$string['popupheightexplain'] = 'Especifica a altura padrão das janelas pop-up.';
$string['popupwidth'] = 'Largura do pop-up (em pixels)';
$string['popupwidthexplain'] = 'Especifica a largura padrão das janelas pop-up.';
$string['printintro'] = 'Mostrar descrição do recurso';
$string['printintroexplain'] = 'Mostrar a descrição do recurso abaixo do conteúdo? Alguns tipos de exibição podem não mostrar a descrição, mesmo se ativados.';
$string['requiredfiles'] = 'ficheiro necessário';
$string['selectfiles'] = "Selecione o ficheiro";
$string['showdate'] = 'Mostrar data de upload / modificação';
$string['showdate_desc'] = 'Mostrar data de upload/modificação na página do curso?';
$string['showdate_help'] = 'Exibe a data de upload/modificação ao lado de links para o ficheiro.
Se houver vários ficheiros neste recurso, a data de modificação/upload do ficheiro inicial será exibida.';
$string['showsize'] = 'Mostrar tamanho';
$string['showsize_desc'] = 'Mostrar o tamanho do ficheiro na página do curso?';
$string['showsize_help'] = 'Exibe o tamanho do ficheiro, como \'3,1 MB \', ao lado dos links para o ficheiro.
Se houver vários ficheiros neste recurso, o tamanho total de todos os ficheiros será exibido.';
$string['showtype'] = 'Mostrar tipo';
$string['showtype_desc'] = 'Mostrar tipo de ficheiro (por exemplo, \'documento do Word \') na página do curso? ';
$string['showtype_help'] = 'Exibe o tipo do ficheiro, como \'PDF \', ao lado dos links para o ficheiro.
Se houver vários ficheiros neste recurso, o tipo de ficheiro inicial será exibido.';
$string['uploadeddate'] = 'Carregado {$a}';
$string['versionnotallowed'] = 'O PDF está em uma versão maior que 1.4. Versões compatíveis: 1.0, 1.1, 1.2, 1.3, 1.4. Recomendamos o uso da ferramenta <a href="https://docupub.com/pdfconvert">Docupub</a> para alteração da versão.';
$string['moodlewatermark:addinstance'] = 'Adicionar novo ficheiro com marca d\'água';
$string['moodlewatermarkdetails_sizedate'] = '{$a->size} {$a->date}';
$string['moodlewatermarkdetails_sizetype'] = '{$a->size} {$a->type}';
$string['moodlewatermarkdetails_sizetypedate'] = '{$a->size} {$a->type} {$a->date}';
$string['moodlewatermarkdetails_typedate'] = '{$a->type} {$a->date}';
$string['moodlewatermark:view'] = 'Visualizar ficheiro com marca d\'água';
