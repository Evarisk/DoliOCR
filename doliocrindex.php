<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       doliocrindex.php
 *	\ingroup    doliocr
 *	\brief      Home page of doliocr top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
	$res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT']. '/main.inc.php';
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)). '/main.inc.php')) {
	$res = @include substr($tmp, 0, ($i + 1)). '/main.inc.php';
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))). '/main.inc.php')) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))). '/main.inc.php';
}
// Try main.inc.php using relative path
if (!$res && file_exists('../main.inc.php')) {
	$res = @include '../main.inc.php';
}
if (!$res && file_exists('../../main.inc.php')) {
	$res = @include '../../main.inc.php';
}
if (!$res && file_exists('../../../main.inc.php')) {
	$res = @include '../../../main.inc.php';
}
if (!$res) {
	die('Include of main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(['doliocr@doliocr', 'admin', 'errors']);

// Get parameters
$action = GETPOST('action', 'aZ09');

$upload_dir = $conf->doliocr->multidir_output[$conf->entity];

// Security check
$permissiontoread = $user->rights->doliocr->lire;
if (empty($conf->doliocr->enabled)) accessforbidden();
if (!$permissiontoread) accessforbidden();

/*
 *  Actions
*/

if ($action == 'convertTxtToCSV') {
    $pdffilename    = GETPOST('pdffilename');
    $txtfilename    = GETPOST('txtfilename');
    $searchfilename = GETPOST('searchfilename');

    // Open the file
    $readFile = fopen($upload_dir . '/' . $txtfilename, 'r');

    // Convert Txt to Array
    if ($readFile) {
        $txtArray = explode("\n", fread($readFile, filesize($upload_dir . '/' . $txtfilename)));
    }
    fclose($readFile);

    // Open the file
    $readFile = fopen($upload_dir . '/' . $searchfilename, 'r');
    if ($readFile) {
        while (!feof($readFile)) {
            $searchArray = fgetcsv($readFile, 1000, ',');
        }
    }
    fclose($readFile);

    // Count number of occurences for serchValue in txtArray provide by searchArray
    $searchArray = explode(';', $searchArray[0]);
    foreach ($searchArray as $serchValue) {
        $csvArray[$serchValue] = substr_count($txtArray[0], $serchValue);
    }

    // Open a file in write mode ('w')
    $now = dol_now();
    $csvfilename = dol_print_date($now, 'dayxcard') . '_result.csv';

    $readFile = fopen($upload_dir . '/' . $csvfilename, 'w');

    if (is_array($csvArray) && !empty($csvArray)) {
        // Loop through file pointer and a line
        $header = [$langs->trans('ReadFile') => $pdffilename, $langs->transnoentities('ParsedFile') => $txtfilename];
        $headerArray = array_merge(array_keys($header), $searchArray);
        $dataArray = array_merge(array_values($header), $csvArray);
        fputcsv($readFile, $headerArray);
        $i = 0;
//        foreach ($final as $row) {
            //array_unshift($row, array_keys($final)[$i]);
            fputcsv($readFile, $dataArray);
            $i++;
       // }
        setEventMessages($langs->trans('SuccessGenerateCSV', $csvfilename), []);
    } else {
        setEventMessages($langs->trans('ErrorMissingData'), [], 'errors');
    }
    fclose($readFile);
    exit;
}


/*
 * View
 */

$help_url = 'FR:Module_DoliOCR';
$title    = $langs->trans('DoliOCRArea');
//$morejs   = ["/doliocr/js/doliocr.js"];
//$morecss  = ["/doliocr/css/doliocr.css"];

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

print load_fiche_titre($title, '', 'doliocr_color.png@doliocr');

print load_fiche_titre($langs->trans('SecurityProblem'), '', ''); ?>

<input type='hidden' id="token" value="<?php echo newToken(); ?>" />
<input id='uploadSerchCSV' type='file' />
<input id='uploadPDF' type='file' />

<textarea id='inputText'></textarea>

<!--<script src='https://unpkg.com/tesseract.js@4.0.0/dist/tesseract.min.js'></script>-->
<!--<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.1.81/build/pdf.min.js"></script>-->
<script src="<?php echo DOL_URL_ROOT . '/custom/doliocr/js/tesseract/tesseract.min.js'; ?>"></script>
<script src="<?php echo DOL_URL_ROOT . '/custom/doliocr/js/pdf/pdf.min.js'; ?>"></script>
<script src="<?php echo DOL_URL_ROOT . '/custom/doliocr/js/doliocr.js'; ?>"></script>

<?php
// End of page
llxFooter();
$db->close();
