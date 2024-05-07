<?php   $DocFil= './Proj.demo/translate.page.php';    $DocVer='1.3.1';    $DocRev='2024-05-07';      $DocIni='evs';  $ModulNr=0; ## File informative only
$©= 'Open source - 𝘓𝘐𝘊𝘌𝘕𝘚𝘌 & 𝘊𝘰𝘱𝘺𝘳𝘪𝘨𝘩𝘵 ©  2019-2022 EV-soft *** See the file: LICENSE';

$sys= $GLOBALS["gbl_ProgRoot"]= './';

## Activate needed libraries: Set 0:deactive  1:Local-source  2:WEB-source-CDN
$needJquery=      '2';
$needTablesorter= '2';
$needPolyfill=    '0';
$needFontawesome= '2';
$needTinymce=     '0';

require_once ($sys.'php2html.lib.php');
// require_once ($sys.'menu.inc.php');
require_once ($sys.'translate.inc.php');
require_once ($sys.'filedata.inc.php');

if (!isset($_SESSION['currLang'])) {
    $_SESSION['currLang']= 'en';
    $_SESSION['native']= 'English';
}
         # $titl='',$hint='',$info='',$inis='',$algn='center', $gbl_Imag='',$gbl_Bord=true) 
htm_Page_( titl:'translate.page.php', hint:'@Maintenance of project translation', info:'', inis:'', algn:'center',  imag:$gbl_ProgRoot.'_accessories/_background.png', pbrd:true);
    // Menu_Topdropdown(true); 
    // htm_nl(1); 
    
    // $menudata is set in: project.init.php
    $menudata= [ // Data(0:vrnt='', 1;icon='', 2:labl='', 3:hint='', 4:desc='', 5:link='', 6:subm=[], 7:styl='')
        ['Frst','fas fa-info colrwhite',    '@INTRODUCTION','', '@Here you can read about the systems most useful modules:','PHP2HTML-intro.page.php',[
            ['Next','fas fa-info black',    '@PHP2HTML ?',  '', '@What is Clever html engine ?','PHP2HTML-intro.page.php',[],''],
            ['Next','fas fa-info black',    '@Description', '', '@What is PHP2HTML ?','description.page.php',[],''],
            ['Next','fas fa-info black',    '@Demo',        '', '@See and try examples of nearly all modules in the system...','Demo.page.php',[],''],
            ['Next','fas fa-info black',    '@Tiny editor', '', '@Try the advanced online HTML editor','tinyEditor.page.php ',[],''],
            ['Next','fa-regular fa-credit-card black', '@ Advanced example', '','@Example of an Accounting program','CustomerOrder.page.php',[],''],
            ['Next','fa-regular fa-credit-card black', '@ Danish account plan', '','@Example of an account plan','accountPlan.page.php',[],''],
            ['Last','fa-solid fa-book black', '@ Documentation', '','@Documentation of the system','documentation.page.php',[],'']
           
        ]],
        ['Next','fas fa-cubes colrorange',  '@MODULES',          '@Systm modules',                      '@','input.page.php', [
            ['Next','fas fa-cubes black',   '@htm_Input',        '@htm_Input() used for input and output of values of various variables','@','input.page.php', [],'top:350px;'],
            ['Next','fas fa-cubes black',   '@htm_Table',        '@Example on module htm_Table()',      '@','table.page.php', [] ,'top:350px;'],
            ['Next','fas fa-cubes black',   '@htm_Card',         '@Example and notes about htm_Card()', '@','card.page.php', [] ,'top:350px;'],
            ['Next','fas fa-cubes black',   '@Page layout',      '@About making page layout',           '@','pages.page.php', [] ,'top:350px;'],
            ['Next','fas fa-cubes black',   '@Navigate',         '@About menues an link buttons',       '@','navigate.page.php',[] ,'top:350px;'],
            ['Last','fas fa-cubes black',   '@Others',           '@Buttons, Messages and dialog',       '@','other.page.php',[] ,'top:350px;']
        ],'top:350px;'],
        ['Next','fas fa-file colryellow ',  '@FILES',            '@Go to files.page.php',               '@','files.page.php', [
            ['Next','fas fa-file black',    '@File naming',      '@Go to files.page.php',               '@','files.page.php', [],'top:500px;'],
            ['Next','fas fa-file black',    '@Folders and files','@Go to support.page.php',             '@','support.page.php', [],'top:500px;'],
            ['Last','fas fa-info black',    '@Quickstart',       '@How to start the system',            '@','../Quick_Proj/quickstart.page.php', [],'top:500px;']
        ],'top:500px;'],
        ['Next','fas fa-language colrblue', '@TRANSLATE',        '@Go to translate.page.php',           '@','translate.page.php', [],'top:500px;'],
        ['Last','fas fa-code colrcyan',     '@FUNCTIONS',        '@Go to functions.page.php',           '@','functions.page.php', [
            ['Last','fas fa-code colrcyan', '@Overview',         '@Go to functions.page.php',           '@','functions.page.php', [],'top:500px;'],
        ],'top:500px;']
        ]; 
         
    $menunote= '@<small>Test site for Folder-Explorer • Errors can occur and future news may be shown • ver.'.$DocVers.': 50+ core functions.</small>';
    htm_Menu_TopDown(capt:'Clever html engine',data:$menudata, foot:'PHP2HTML', styl:'top:0px;', note:$menunote); 
    htm_nl(2);

    global $arrLang;
    // arrPrint($arrLang,'$arrLang');

    htm_Caption( labl:'@Maintenance of project translation', icon:'', hint:'', algn:'center', styl:'color:'.$gbl_TitleColr.'; font-weight:600; font-size: 18px;');
    htm_nl(1);
    // htm_TextDiv('@Maintenance of project translation','center');
              # $capt= '',$icon= '',$hint= '',$form= '',$acti= '',$clas= 'cardWmax',$wdth= '',$styl= 'background-color: white;',$attr= ''
    htm_Card_( capt:'@About translate system:',  icon:'fas fa-info',  hint: '', form: '', acti: '', clas:'cardW560',  wdth: '', styl: 'background-color: white;', attr: '');
    echo '<div style="text-align: left; margin: 20px;">
        All english textstrings to be translated, should have prefix \'@ 
        in the source. <br>It will be translated with function lang(\'English text\') <br><br>
        To create the table with strings to translate a function will scann all the
        source after prefix: <b>lang(\'</b>  .. and with suffix: <b>\')</b><br>
        Other prefix: <b>mess(\'</b>    (See more in file translate.inc.php)<br><br>
        Strings without these prefixes must have prefix: \'@ so it can be found.<br><br>
        All translated languages is defined in file: _trans.sys.json <br>
        If there are no translation, the english text will output with prefix @ removed
        <br><br>
        </div>';
    htm_Card_end();
    htm_nl(2);

    // $ISO639= ReadCSV($filepath='ISO639-1.csv');    // arrPrint($ISO639,'ISO639'); 
    global $arrLang, $alllang, $App_Conf;
    foreach ($arrLang as $lng) {
        $SelList[]= [$lng["code"],$lng["code"].' : '.$lng["name"],$lng["native"].' - Author: '.$lng["author"].' - '.$lng["note"]];}
    if (isset($_POST['langu'])) {
        $App_Conf['language'] = $_POST['langu']; 
        $_SESSION['proglang'] = $_POST['langu'];
    }

              # $capt= '',$icon= '',$hint= '',$form= '',$acti= '',$clas= 'cardWmax',$wdth= '',$styl= 'background-color: white;',$attr= ''
    htm_Card_( capt:'@Select a language:',  icon:'fas fa-wrench', hint: '', form: 'lang', acti: '', clas:'cardW560', wdth: '', styl: 'background-color: white;', attr: '');
    echo '<div style="text-align: center; margin: 20px;">';  
    echo lang('The actual language is').'<b> '.$App_Conf['language'].' / '/* .$_SESSION['currLang']['native'] */.' </b><br><br>';
# PHP7: $labl='',$plho='@Enter...',$icon='',$hint='',$type= 'text',$name='',$valu='',$form='',$wdth='',$algn='left',$attr='',$proc=true,$unit='',$disa=false,$rows='2',$step='',$list=[],$llgn='R',$bord='',$ftop='');
    htm_Input( labl:'@Filter', plho:'Enter...', icon:'', hint:'@Hide/show some (empty) languages in the language selector',
               vrnt:'rado', name:'alllang', valu:$alllang, form:'', wdth:'110px', algn:'left', attr:'onclick="this.form.submit();"', rtrn:false, unit:'', disa:true, rows:'2', step:'',
               list: [
                 ['All','All','@Show the complete list','checked'],
                 ['Som','Some','@Hide all empty languages'],
              ]);
    htm_Input( labl:'@Select another language', plho:'@Sel...', icon:'', hint:'@Select amongst installed languages',
               vrnt:'opti', name:'langu', valu:'dsads', form:'', wdth:'200px', algn:'left', attr:'', rtrn:false, unit:'', disa:false, rows:'3', step:'', list: $SelList);
    echo '</div>';
    htm_Card_end( labl:'@Activate selected', icon:'',  hint:'@Change language to the selected',  name:'',  form:'lang', subm:true,  attr:'',  akey:'',  kind:'save', simu:false);
    htm_nl(2);

    
              # $capt= '',$icon= '',$hint= '',$form= '',$acti= '',$clas= 'cardWmax',$wdth= '',$styl= 'background-color: white;',$attr= ''
    htm_Card_( capt:'@Translating language strings:', icon:'fas fa-tools', hint: '', form: '', acti: '', clas:'cardW960', wdth: '', styl: 'background-color: white;', attr: '');
    echo '<div style="text-align: left; margin: 20px;">';            
    scannLngStrings($code= substr($App_Conf['language'],0,2));
    echo '</div>';
    htm_Card_end();
    htm_nl(2);
htm_Page_end();

?>
