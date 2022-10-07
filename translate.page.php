<?php   $DocFil= './translate.page.php';    $DocVer='1.2.0';    $DocRev='2022-10-07';     $DocIni='evs';  $ModulNr=0; ## File informative only
## 𝘓𝘐𝘊𝘌𝘕𝘚𝘌 & 𝘊𝘰𝘱𝘺𝘳𝘪𝘨𝘩𝘵 ©  2019-2022 EV-soft *** 
require_once ('php2html.lib.php');
// require_once ('menu.inc.php');
require_once ('translate.inc.php');
require_once ('filedata.inc.php');

## Speedup page-loading, if some libraryes is not needed:
//      ConstName:          ix:   LocalPath:                 CDN-path:
define('LIB_JQUERY',        [1, '_assets/jquery/',          'https://cdnjs.cloudflare.com/ajax/libs/']);
define('LIB_TABLESORTER',   [1, '_assets/tablesorter/js/',  'https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.30.1/js/']);
define('LIB_POLYFILL',      [0, '_assets/',  '']);
define('LIB_POPSCRIPTS',    [0, '_assets/',  '']);
define('LIB_FONTAWESOME',   [1, '_assets/font-awesome6/',   'https://cdnjs.cloudflare.com/ajax/libs/font-awesome6/']);
// Set ix 0:deactive  1:Local-source  2:WEB-source-CDN



htm_Page_0($titl='translate.page.php', $hint='', $info='', $inis='', $algn='center', $gbl_Imag='_assets/images/_background.png');
    // Menu_Topdropdown(true); htm_nl(1);
    
    global $arrLang;
    // arrPrint($arrLang,'$arrLang');

    echo '<div style="text-align: center; background-image: url(\'_assets/images/_background.png\');">';
    # $capt= '',$icon= '',$hint= '',$form= '',$acti= '',$clas= 'panelWmax',$wdth= '',$styl= 'background-color: white;',$attr= '',$show = true,$head = '' 
    htm_Panel_0($capt='@About translate system:', $icon='fas fa-info', $hint= '',$form='', $acti= '',$clas='panelW560',
                $wdth= '',$styl='background-color: white;');
    echo '<div style="text-align: left; margin: 20px;">
        All english textstrings that should be translated, can have prefix \'@ <br>
        in the source. It will be translated with function lang(\'English text\') <br><br>
        To create the table with strings to translate a function will scann all the
        source after prefix: <b>lang(\'</b>  .. and with suffix: <b>\')</b><br>
        Other prefix: <b>mess(\'</b>    (See more in file translate.inc.php)<br><br>
        Strings without these prefixes must have prefix: \'@ so it can be found.<br><br>
        All translated languages is defined in file: sys_trans.json <br>
        If there are no translation, the english text will output with prefix @ removed
        <br><br>
        </div>';
    htm_Panel_00();
    htm_nl(2);

    // $ISO639= ReadCSV($filepath='ISO639-1.csv');    // arrPrint($ISO639,'ISO639'); 
    global $arrLang, $App_Conf;
    foreach ($arrLang as $lng) {
        $SelList[]= [$lng["code"],$lng["code"].' : '.$lng["name"],$lng["native"].' - Author: '.$lng["author"].' - '.$lng["note"]];}
    if (isset($_POST['langu'])) {
        $App_Conf['language'] = $_POST['langu']; 
        $_SESSION['proglang'] =  $_POST['langu'];
    }
    if (isset($_POST['alllang'])) $alllang = $_POST['alllang']; else $alllang= '';

    # $capt= '',$icon= '',$hint= '',$form= '',$acti= '',$clas= 'panelWmax',$wdth= '',$styl= 'background-color: white;',$attr= '',$show = true,$head = '' 
    htm_Panel_0($capt='@Select a language:', $icon='fas fa-wrench', $hint= '',$form='lang', $acti= '',$clas='panelW560', $wdth= '',$styl='background-color: white;');
    echo '<div style="text-align: center; margin: 20px;">';  
    echo lang('The actual language is').'<b> '.$App_Conf['language'].' / '.($_SESSION['currLang']['native'] ?? '') .' </b><br><br>';
    # $type='',$name='',$valu='',$labl='',$hint='',$plho='@Enter...',$wdth='',$algn='left',$unit='',$disa=false,$rows='2',$step='',$more='',$list=[],$llgn='R',$bord='',$proc=true);
            # $labl='',$plho='@Enter...',$icon='',$hint='',$type= 'text',$name='',$valu='',$form='',$wdth='',$algn='left',$attr='',$rtrn=false,$unit='',$disa=false,$rows='2',$step='',$list=[],$llgn='R',$bord='',$ftop='');
    htm_Input($labl='@Filter',$plho='Enter...',$icon='',$hint='@Hide/show some (empty) languages in the language selector',$type='rado',$name='alllang',$valu=$alllang,$form='',
              $wdth='110px', $algn='left',$attr='onclick="this.form.submit();"',$rtrn=false,$unit='',$disa=true,$rows='2',$step='',$list= [
    ['All','All','@Show the complete list','checked'],
    ['Som','Some','@Hide all empty languages'],
    ],$llgn='R',$bord='',$ftop='');
            # $labl='',$plho='@Enter...',$icon='',$hint='',$type= 'text',$name='',$valu='',$form='',$wdth='',$algn='left',$attr='',$rtrn=false,$unit='',$disa=false,$rows='2',$step='',$list=[],$llgn='R',$bord='',$ftop='');
    htm_Input($labl='@Select another language',$plho='@Select...',$icon='',$hint='@Select amongst installed languages',$type='opti',$name='langu',$valu=$App_Conf['language'],$form='',
              $wdth='200px', $algn='left',$attr='',$rtrn=false,$unit='',$disa=false,$rows='3',$step='',$list= $SelList,$llgn='R',$bord='',$ftop='');
    echo '</div>';
    #htm_Panel_00( # $labl='', $icon='', $hint='', $name='', $form='',$subm=false, $attr='', $akey='', $kind='save', $simu=false)
    htm_Panel_00($labl='Activate selected', $icon='', $hint='@Change language to the selected', $name='', $form='lang', $subm=true, $attr='', $akey='',  $kind='save', $simu=false);
    htm_nl(2);

    
    # $capt= '',$icon= '',$hint= '',$form= '',$acti= '',$clas= 'panelWmax',$wdth= '',$styl= 'background-color: white;',$attr= '',$show = true,$head = '' 
    htm_Panel_0($capt='Translate language strings:', $icon='fas fa-tools', $hint= '',$form='', $acti= '',$clas='panelW960',
                $wdth= '560px',$styl='background-color: white;');
    echo '<div style="text-align: left; margin: 20px;">';            
    scannLngStrings($code= substr($App_Conf['language'],0,2));
    echo '</div>';
    htm_Panel_00();
    htm_nl(2);

    echo '</div>';
    
    PanelOff($First=1,$Last=3);
    PanelOn($noFrom=2);
htm_Page_00();
?>