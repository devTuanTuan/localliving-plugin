<?php
global $wpdb;

$table_name_options = $wpdb->prefix . 'localliving_plg_options';

$defaultFrontpageBgUrl = WP_PLUGIN_DIR.'/localliving-plugin/assets/images/pdf/bg.png';

$frontpageBgUrl = '';

$query = "SELECT *
        FROM $table_name_options
        WHERE $table_name_options.option_name = 'pdf_frontpage_background_img'";

$queryResult = $wpdb->get_row($query);

if(isset($queryResult->option_name)) {
	$frontpageBgUrl = $queryResult->option_value;
}

if($frontpageBgUrl == '') {
	$frontpageBgUrl = $defaultFrontpageBgUrl;
}

$pdfStyle =
'
@page frontpage {
    margin-top: 60px;
    position: relative;
    background: url("'.$frontpageBgUrl.'") no-repeat;
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
div.extra-costs-description {
	margin-top: 10px;
}
div.extra-costs-description p {
	font-size: 11px;
	margin: 0;
}
div.extra-costs-description a {
	color: #000000;
	font-size: 11px;
	margin: 0;
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
';
