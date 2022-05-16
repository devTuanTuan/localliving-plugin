<?php

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\MpdfException;

require_once(__DIR__ . '/vendor/autoload.php');

$defaultConfig = (new ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

try {
    $mpdf = new Mpdf([
        'fontDir' => array_merge($fontDirs, [
            __DIR__ . '/assets/font',
        ]),
        'fontdata' => $fontData + [
                'lemontuesday' => [
                    'R' => 'LemonTuesday.ttf',
                ],
                'opensans' => [
                    'R' => 'OpenSans-Regular.ttf',
                    'B' => 'OpenSans-Bold.ttf',
                    'I' => 'OpenSans-Italic.ttf',
                ],
                'opensanslight' => [
                    'R' => 'OpenSans-Light.ttf',
                ],
                'merriweather' => [
                    'R' => 'Merriweather-Regular.ttf',
                    'B' => 'Merriweather-Bold.ttf',
                ],
            ],
        'default_font' => 'opensans'
    ]);
    
    $html = '
        <pagebreak page-selector="frontpage"/>
        <div class="logo">
            <img width="340" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/logo-with-text.png"/>
        </div>
        <div class="footer">
            <h1 class="footer-heading">Autentiske ferieoplevelser i Italien</h1>
            <p class="footer-desc">Local Living A/S · <a style="color: #fff;text-decoration: none" href="https://localliving.dk/">www.localliving.dk</a> · Tel: <a href="tel:+45.28157241" style="color:white;text-decoration: none">+45 28 15 72 41</a> · <a style="color: #fff;text-decoration: none" href="mailto:info@localliving.dk">info@localliving.dk</a></p>
        </div>

        <pagebreak page-selector="greetingpage"/>
        
        <div class="user-info">
            <div class="user-info-desc">
                <h1 class="text-primary">STOR FAMILIE FERIE I UGE 28</h1>
                <p>Hej Anders,</p>
                <p>Jeg har et dejligt sted til jer i det nordlige Toscana – Agriturismo Popolano di 
                Sotto. Her er lige netop 4 lejligheder til jer til den rigtige pris, og der er et 
                fællesområde, hvor I kan spise måltiderne sammen. Se de ﬁre lejligheder i 
                vedhæftede tilbud.</p>
                <p>Vi har også et andet dejligt sted, hvor der også er god plads til jer og et sted, 
                hvor I kan spise sammen – og det er La Coppa. Her vil jeg dog anbefale, at I 
                tager alle lejlighederne selvom der er 5 i alt, så I har stedet helt for jer selv, 
                og også fordi 2 af lejlighederne er meget små.</p>
                <p>Hvad siger I til det?</p>
                <p>Jeg håber, det matcher jeres ønsker, og jeg står meget gerne til rådighed 
                med yderligere informationer om stederne. Hvis I har brug for ﬂere alterna-
                tiver, må du endelig sige til.</p>
                <p>Jeg ser meget frem til at høre fra dig igen :)</p>
                <p>Med venlig hilsen – tanti saluti <br/>Inge Gustafsson</p>
            </div>
            <div class="user-info-contact">
                <img width="150" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/avatar.png"/>
                <h2 class="text-primary">HAR DU SPØRGSMÅL?</h2>
                <p>Vi står klar til at hjælpe!</p>
                <div><strong>Inge Gustafsson</strong></div>
                <div>Email: <a style="color: #000;text-decoration: none" href="mailto:inge@localliving.dk">inge@localliving.dk</a></div>
                <div>Telefon: <a href="tel:+45.28157241" style="color:#000;text-decoration: none">+45 28 15 72 41</a></div>
                <div>(mellem kl. 9.00 – 17.00)</div>
            </div>
        </div>
        

        <pagebreak page-selector="accomodation"/>

        <div class="stars">
            <span class="star"><img class="star-img" width="18px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/cat-medium2.png"/></span>
            <span class="star"><img class="star-img" width="18px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/cat-medium2.png"/></span>
            <span class="star"><img class="star-img" width="18px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/cat-medium2.png"/></span>
            <span class="plus">+</span>
        </div>
        <div class="accomodation-border search-results-row-toscana"></div>
        <h1 class="accomodation-title text-primary">AGRITURISMO POPOLANO DI SOTTO</h1>
        <div class="region">Ferielejlighed - Toscana</div>
        <div class="gallery">
            <div class="gallery-main">
                <img width="470px" height="312px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/main-img.jpg"/>
            </div>
            <div class="gallery-sub">
                <div class="gallery-sub-image">
                    <img width="223px" height="148px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/sub-img-1.jpg"/>
                </div>
                <div class="gallery-sub-image">
                    <img width="223px" height="148px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/sub-img-2.jpg"/>
                </div>
            </div>
        </div>
        <div class="accomodation-description">
            <ul>
                <li>Meget hjælpsom ejer, der laver pizza til jer</li>
                <li>Lille sted med 4 lejligheder</li>
                <li>Egen produktion af druer og valnødder</li>
            </ul>
            <div>Dejligt lille agriturismo er drevet med stor entusiasme. Det er muligt at være med ved kastanjehøsten i oktober. Fisk i ﬂoden, tage en cykeltur eller lav pizza i den gamle ﬁne ovn. Beliggende i den nordøstlige del af Toscana.</div>
            <div class="accomodation-description-hjemmesiden">
                <a href="#" class="heading-custom">
                    Læs mere på hjemmesiden
                </a>
            </div>
        </div>
        <div class="accomodation-more-photo">
            <a href="#" class="heading-custom accomodation-more-photo-text-link">Se flere billeder her</a>
            <div class="accomodation-more-photo-bg">
                <img width="30px" src="'.WP_PLUGIN_DIR.'/localliving-plugin//assets/images/pdf/billeder-her-img.png"/>
            </div>
        </div>
        <div class="facilities-wrapper">
            <h2 class="text-primary">FACILITETER</h2>
            <div class="facilities">
                <div class="facilities-list">
                    <ul>
                        <li style="width:35%">Swimmingpool</li>
                        <li style="width:35%">Grønsagshave</li>
                        <li style="width:30%">Internet</li>
                    </ul>
                    <ul>
                        <li style="width:35%">Kæledyr tilladt</li>
                        <li style="width:35%">Barbecue</li>
                        <li style="width:30%">Ekstraseng</li>
                    </ul>
                    <ul>
                        <li style="width:35%">Vaskemaskine</li>
                        <li style="width:35%">Separat betaling for afrejserengøring</li>
                        <li style="width:30%">Meget, meget stille</li>
                    </ul>
                    <ul>
                        <li style="width:35%">Høj stol</li>
                        <li style="width:35%">Små kæledyr</li>
                        <li style="width:30%">Ekstra rengøring</li>
                    </ul>
                    <ul>
                        <li style="width:35%">Babyseng</li>
                        <li style="width:35%">Kamin</li>
                        <li style="width:30%">Pejs eller pizzaovn</li>
                    </ul>
                    <ul>
                        <li style="width:35%">Separat betaling for afrejserengøring</li>
                        <li style="width:35%">Satellit TV</li>
                        <li style="width:30%">Hårtørrer</li>
                    </ul>
                </div>
            </div>
        </div>

        <pagebreak page-selector="accomodation-unit"/>

        <div class="accomodation-border search-results-row-toscana"></div>
        <h1 class="accomodation-unit-title text-primary">AGRITURISMO POPOLANO DI SOTTO</h1>
        <h2 class="accomodation-unit-sub-title text-primary">IL FICO (4 - 5 PERS.)</h2>
        <div class="unit-description">
            <p>2-værelselses lejlighed på ca. 40 m2.</p>
            <p>Dobbeltværelse, spisestue med dobbelt sofaseng, balkon. Billederne er en blanding af foto fra de forskellige
            lejligheder.</p>
        </div>
        <div class="gallery">
            <div class="gallery-main">
                <img width="470px" height="312px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/main-img-2.jpg"/>
            </div>
            <div class="gallery-sub">
                <div class="gallery-sub-image">
                    <img width="227px" height="151px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/sub-img-2-1.jpg"/>
                </div>
                <div class="gallery-sub-image">
                    <img width="227px" height="151px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/sub-img-2-2.jpg"/>
                </div>
            </div>
            <div class="gallery-sub-2">
                <div class="gallery-sub-2-image">
                    <img width="227px" height="151px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/sub-img-2-3.jpg"/>
                </div>
                <div class="gallery-sub-2-image">
                    <img width="227px" height="151px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/sub-img-2-4.jpg"/>
                </div>
                <div class="gallery-sub-2-image last">
                    <img width="227px" height="151px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/sub-img-2-5.jpg"/>
                </div>
            </div>
        </div>
        <div class="accomodation-unit-more-photo">
            <a href="#" class="heading-custom accomodation-more-photo-text-link">Se flere billeder her</a>
            <div class="accomodation-more-photo-bg">
                <img width="30px" src="'.WP_PLUGIN_DIR.'/localliving-plugin//assets/images/pdf/billeder-her-img.png"/>
            </div>
        </div>
        <h2 class="text-primary">PRISER I KR. FOR HELE FERIELEJLIGHEDEN</h2>
        <div class="accomodation-arrow">
            <img src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/arrow-down.png" width="15px"/>
        </div>
        <table class="accomodation-prices" border="0" cellSpacing="0">
            <tr>
                <th class="column-1" width="30%" align="left"><strong>PRIS I KR. PR. UGE</strong></th>
                <th class="column-2" width="20%" align="center"><strong>AFREJSE -<br/> RENGØRING</strong></th>
                <th class="column-3" width="20%" align="left"><strong>22.12.2021 -<br/> 29.12.2021</strong></th>
                <th class="column-4" colspan="2" width="30%" align="right">
                    <div class="heading-custom table-title">Book online her</div>
                </th>
            </tr>
            <tr>
                <td>IL FICO (4 - 6 PERS.)</td>
                <td align="center">456,00</td>
                <td colspan="2">
                    <div><span class="old-price">3.557,00</span> 2.557,00</div>
                    TIDLIG BOOKING RABAT - 20%
                </td>
                <td align="right"><a class="booking" href="#">BOOK NU</a></td>
            </tr>
        </table>
        <p class="small-text">Prisen inkluderer gas, vand, el, internet i fællesområde, afrejserengøring, sengelinned og håndklæder - dog ikke poolhåndklæder.<br/>Swimmingpoolen er åben i perioden 16/04 - 03/10.</p>
        
        <pagebreak page-selector="about"/>

        <div class="about-page">
            <h1 class="text-primary">OM LOCAL LIVING</h1>
            <div class="about-desc-left">
                <p>Specialister i rejser til håndplukkede ferielejligheder og villaer i italien.</p>
                <p>Ønsker du indimellem at rejse til steder med oprigtig charme? Hvor du har følelsen af at være dig selv midt i fantastiske naturscenarier, og hvor du oplever den lokale charme – helt tæt på?</p>
                <p>– Nu har du muligheden for at opleve dette og bo på et italiensk landsted i det farverige Italien. Her vågner du op i den dejligste natur, og kan nyde din morgen- kaﬀe med udsigt over de toscanske vinmarker, få en snak med en lokal bonde eller prøve en velsmagende ”cafe latte” på landsbyens lokale café.</p>
                <p>Har du først én gang i livet tabt dit hjerte til denne rejseform, er der ingen vej tilbage. Fred og ro er nøgleordene, og du bestemmer selv hvilken pano- ramaudsigt du vil vågne op til.</p>
                <p>Vi har færdigsyet rejseoplevelsen til dig, der gerne vil opleve Local Living i Toscana, Umbrien, Ligurien, Piemonte og Sicilien.</p>
            </div>
            <div class="about-desc-right">
                <p>Local Living er så meget mere end en almindelig ferie. Det er livsglæde for alle, der elsker at rejse, at opleve, at gå på opdagelse og ikke mindst at møde et charm- erende, livligt folkefærd.</p>
                <img width="100%" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/about-img.jpg"/>
                <p><i>“Efter mange års rejser i Italien har jeg tabt mit hjerte til skønne Italien. Det er oplevelsernes paradis – lige fra de skønne vine, den dejlige mad, det vidunderlige landskab og til den lokale, gæstfrie befolkning. Det er Local Living – helt tæt på!”</i></p>
                <p class="text-right">– Inge Gustafsson</p>
            </div>
        </div>
        
        <htmlpageheader name="MyHeader">
            <div class="header">
                <img width="225" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/logo.png"/>
            </div>
        </htmlpageheader>
        <htmlpagefooter name="MyFooter">
            <div class="footer-left">Medlem af Rejsegarantifonden reg. nr. 2071 · e-mærket · grundlagt i 2005</div>
            <div class="footer-right">S. {PAGENO} af {nbpg}</div>
        </htmlpagefooter>
        <htmlpagefooter name="MyFooterGreeting">
            <h2 class="text-primary">JERES NØJE UDVALGTE FERIEBOLIGER</h2>
            <div class="location">
                <div class="location-map">
                    <img class="location-map-img" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/map.png"/>
                </div>
                <div class="location-list">
                    <ol>
                        <li><a href="#">1. Agriturismo Popolano Di Sotto</a></li>
                        <li><a href="#">2. Agriturismo La Coppa</a></li>
                    </ol>
                    <div class="location-list-arrow">
                        <img width="28px" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/arrow.png"/>
                    </div>
                    <div class="location-list-desc heading-custom">
                        Klik for at se<br/>på hjemmesiden
                    </div>
                </div>
            </div>
            <div class="footer-left">Medlem af Rejsegarantifonden reg. nr. 2071 · e-mærket · grundlagt i 2005</div>
            <div class="footer-right">S. {PAGENO} af {nbpg}</div>
        </htmlpagefooter>
        <htmlpagefooter name="MyFooterAbout">
            <div class="about-footer-wrapper">
                <div class="about-footer">
                    <div class="about-user">
                        <div class="about-user-avatar">
                            <img width="155" src="'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/avatar.png"/>
                        </div>
                        <div class="about-user-contact">
                            <h2 class="about-user-contact-h2 text-primary">HAR DU SPØRGSMÅL?</h2>
                            <p class="about-user-contact-p">Vi står klar til at hjælpe!</p>
                            <div><strong>Inge Gustafsson</strong></div>
                            <div>Email: <a class="link-style" href="mailto:inge@localliving.dk">inge@localliving.dk</a></div>
                            <div>Telefon: <a class="link-style" href="tel:45.28157241">+45 28 15 72 41</a> (mellem kl. 9.00 – 17.00)</div>
                            <div class="about-user-contact-link">
                                <div>Lad os endelig holde kontakten: </div>
                                <div>Følg med på <a class="link-style" href="https://www.facebook.com/LocalLivingDK">Facebook</a> og <a class="link-style" href="https://www.instagram.com/locallivingdk/">Instagram</a> eller tilmeld dig til vores <a class="link-style" href="https://localliving.us18.list-manage.com/subscribe/post?u=9469fca9e0cc85c18faf26796&id=2e5afd0417">nyhedsbrev</a></div>
                            </div>
                        </div>
                     </div>
                    <div class="footer-about" width="95%">
                        <div class="footer-left footer-about-item">Medlem af Rejsegarantifonden reg. nr. 2071 · e-mærket · grundlagt i 2005</div>
                        <div class="footer-right footer-about-item">S. {PAGENO} af {nbpg}</div>
                    </div>
                </div>
            </div>
        </htmlpagefooter>

        <style>
            @page frontpage {
                margin-top: 60px;
                position: relative;
                background: url("'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/bg.png") no-repeat;
                background-image-resize:6;
                header: "";
                footer: "";
            }
            @page greetingpage {
                margin: 40px;
                margin-top: 140px;
                header: MyHeader;
                footer: MyFooterGreeting;
            }
            @page accomodation,
            @page accomodation-unit {
                margin: 40px;
                margin-top: 140px;
                header: MyHeader;
                footer: MyFooter;
            }
            @page about {
                margin: 40px;
                margin-top: 140px;
                header: MyHeader;
                footer: MyFooterAbout;
            }
            h1 {
                font-size: 21.5px;
                font-weight: bold;
            }
            h2 {
                font-size: 16px;
                font-weight: bold;
            }
            body {
                font-size: 13px;
            }
            ul {
                padding-left: 0;
                margin-left: 15px;
                padding-bottom: 10px;
            }
            div.logo {
                text-align: center;
                padding-top: 50px;
            }
            div.footer {
                position: absolute;
                width: 100%;
                bottom: 40px;
                left: 0;
                text-align: center;
                color: #fff;
            }
            h1.footer-heading {
                font-size: 23px;
                font-weight: normal;
                padding-bottom: 25px;
            }
            p.footer-desc {
                font-size: 12px;
            }
            div.header {
                background-color: #e4ebe3;
                text-align: center;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding-top: 30px;
                padding-bottom: 30px;
            }
            div.user-info-desc {
                width: 65%;
                float: left;
            }
            div.user-info-contact {
                width: 35%;
                float: right;
                text-align: right;
            }
            div.location {
                padding-top: 10px;
                padding-bottom: 30px;
            }
            div.location-map {
                width: 65%;
                float: left;
                padding: 0;
            }
            img.location-map-img {
                width: 100%;
                padding: 0;
            }
            div.location-list {
                width: 35%;
                float: right;
            }
            div.location-list ol {
                margin-top: 0;
                list-style-type: none;
                padding-left: 20px;
            }
            div.location-list li {
                color: #53732e;
            }
            div.location-list li a {
                color: #53732e;
                text-decoration: underline;
            }
            div.location-list-desc {
                padding-left: 15px;
                margin-top: -30px;
            }
            div.heading-custom {
                font-size: 29px;
                font-family: "lemontuesday";
                color: #53732e;
            }
            a.heading-custom {
                font-size: 29px;
                font-family: "lemontuesday";
                color: #53732e;
                text-decoration: none;
            }
            div.location-list-arrow {
                padding-top: 60px;
                padding-right: 15px;
                text-align: right;
            }
            h1.text-primary {
                color: #53732e;
                font-family: "merriweather";
            }
            h2.text-primary {
                color: #53732e;
                font-family: "merriweather";
            }
            div.footer-left {
                font-size: 11px;
                float: left;
                width: 80%;
                font-family: "opensanslight";
            }
            div.footer-right {
                font-size: 11px;
                float: right;
                width: 20%;
                text-align: right;
                font-family: "opensanslight";
            }
            div.stars {
                padding-bottom: 10px;
            }
            img.star-img {
                padding-right: 2px;
            }
            span.plus {
                vertical-align: 4px;
            }
            div.accomodation-border {
                width: 200px;
            }
            h1.accomodation-title.text-primary {
                margin-top: 10px;
                margin-bottom: 0;
            }
            h1.accomodation-unit-title {
                margin-top: 10px;
            }
            h2.accomodation-unit-sub-title.text-primary {
                margin-top: 10px;
                margin-bottom: 10px;
            }
            div.region {
                padding-bottom: 20px;
            }
            div.unit-description p {
                margin-bottom: 0;
                margin-top: 0;
            }
            p.small-text {
                font-size: 11px;
            }
            div.search-results-row-toscana {
                border-top: 7px solid #7fa5b6;
            }
            div.search-results-row-umbrien {
                border-top: 7px solid #b47eb7;
            }
            div.search-results-row-sicilien {
                border-top: 7px solid #57649b;
            }
            div.search-results-row-ligurien {
                border-top: 7px solid #9b5757;
            }
            div.search-results-row-north-italy,
            div.search-results-row-north-piemonte,
            div.search-results-row-north-default {
                border-top: 7px solid #438077;
            }
            div.gallery {
                padding-top: 20px;
            }
            div.gallery-main {
                float: left;
                width: 470px;
            }
            div.gallery-sub  {
                float: right;
                width: 227px;
            }
            div.gallery-sub-2  {
                clear: both;
            }
            div.gallery-sub-2-image {
                float: left;
                width: 227px;
                padding-right: 16px;
            }
            div.gallery-sub-2-image.last {
                padding-right: 0;
            }
            div.gallery-sub-image {
                padding-bottom: 16px;
            }
            div.accomodation-description {
                width: 65%;
                float: left;
            }
            div.accomodation-more-photo {
                width: 35%;
                float: right;
                text-align: right;
                width: 200px;
            }
            div.accomodation-unit-more-photo {
                text-align: right;
                margin-top: 10px;
                width: 200px;
                margin-left: auto;
            }
            div.accomodation-more-photo-bg {
                margin-top: -5px;
            }
            a.accomodation-more-photo-text-link.heading-custom {
                font-size: 20px;
            }
            div.accomodation-arrow {
                position: relative;
                text-align:right;
                margin-right: -5px;
                margin-bottom: -90px;
                padding-top: 40px;
                z-index: 1;
            }
            div.facilities-wrapper {
                clear: both;
            }
            div.facilities-list ul {
                padding-bottom: 0;
                padding-top: 0;
                margin-top: 0;
                margin-bottom: 0;
            }
            div.facilities-list ul li {
                float: left;
                padding-top: 0;
                padding-bottom: 0;
            }
            div.accomodation-description-hjemmesiden {
                background: url("'.WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/hjemmesiden-img.png") no-repeat;
                background-size: 500px auto;
                background-position: 130px bottom;
            }
            table.accomodation-prices th {
                font-weight: 700;
                background: #d9e9e0;
                padding: 15px 10px;
                text-align: left;
                font-family: "merriweather";
            }
            table.accomodation-prices td {
                background: #f1f1ec;
                padding: 15px 10px;
            }
            span.old-price {
                text-decoration: line-through;
            }
            div.about-footer-wrapper {
                background: #f1f1ec;
                width: 100%;
                position: absolute;
                bottom: -50px;
                left: 0;
            }
            div.about-footer {
                padding: 10px 0 0 0;
                width: 100%;
            }
            div.about-user {
                padding-bottom: 40px;
            }
            div.about-user-avatar {
                width: 25%;
                float:left;
                padding-top: 30px;
            }
            div.about-user-contact {
                width: 75%;
            }
            div.about-user-contact-link {
                padding-top: 10px;
            }
            a.link-style {
                color: #8ec54a;
                text-decoration: none;
            }
            h2.about-user-contact-h2 {
                padding-bottom: 10px;
            }
            p.about-user-contact-p {
                padding-bottom: 15px;
            }
            p.text-right {
                text-align: right;
            }
            div.about-desc-left {
                width: 48%;
                float: left;
            }
            div.about-desc-right {
                width: 48%;
                float: right;
            }
            div.footer-about {
                padding-bottom: 45px;
            }
            a.booking {
                color: #000;
                text-decoration: none;
            }
        </style>
    ';
    $mpdf->WriteHTML($html);
    ob_end_clean();
    $mpdf->Output();
} catch (MpdfException $e) {
    var_dump($e);
    die;
}
