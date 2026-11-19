<?php
// Run through PHP CLI after loading wp-load.php; not a public setup endpoint.
if (PHP_SAPI!=='cli' || !defined('ABSPATH')) exit;
if(!class_exists('WPCF7_ContactForm'))throw new RuntimeException('Activate Contact Form 7 first.');
$form_id=absint(get_option('ibc_registration_form'));
$form=$form_id?wpcf7_contact_form($form_id):null;
if(!$form){$form=WPCF7_ContactForm::get_template(['locale'=>'ja']);$form->set_title('IBC OC共通申込（送信しないデモ）');}
$props=$form->get_properties();$props['form']=file_get_contents(__DIR__.'/registration-form.txt');$props['additional_settings']="skip_mail: on\ndo_not_store: true";
$props['messages']['mail_sent_ok']='申込デモが完了しました。実際の申し込み・メール送信は行われていません。';
$props['mail']['recipient']='demo@example.com';$props['mail']['sender']='IBC Demo <demo@example.com>';$props['mail']['subject']='【デモ】OC申し込み';$props['mail']['body']="開催ID: [oc_event]\nプログラムID: [program]\n氏名: [your-name]\nメール: [your-email]";$props['mail']['additional_headers']='';$props['mail_2']['active']=false;
$form->set_properties($props);$form->save();update_option('ibc_registration_form',$form->id());
$page=absint(get_option('ibc_registration_page'));
if(!$page||!get_post($page)){$page=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'オープンキャンパス申込','post_content'=>'[ibc_oc_entry]','post_name'=>'oc-entry'],true);if(is_wp_error($page))throw new RuntimeException($page->get_error_message());update_option('ibc_registration_page',$page);}
foreach(['595','592'] as $n){
 if(get_option('ibc_demo_reference_'.$n))continue;
 $id=wp_insert_post(['post_type'=>'ibc_oc','post_status'=>'publish','post_title'=>$n==='595'?'【申込デモ】60分でわかる学校＆入試説明会':'【申込デモ】各学科の体験プログラム','post_content'=>'フォームの比較用デモです。日時・内容は仮の設定です。'],true);
 if(is_wp_error($id))throw new RuntimeException($id->get_error_message());
 $meta=['start'=>'2026-11-28T'.($n==='595'?'10:00':'13:00'),'end'=>'2026-11-28T'.($n==='595'?'11:00':'16:00'),'session'=>$n==='595'?'午前':'午後','audience'=>'全学年対象・再進学希望者対象・保護者さま対象','venue'=>'IBC校内（デモ）','state'=>'open','interview'=>$n==='595'?'1':'0','registration_enabled'=>'1'];
 foreach($meta as $k=>$v)update_post_meta($id,'_ibc_'.$k,$v);
 $ref=json_decode(file_get_contents(dirname(ABSPATH).'/docs/reference-form-'.$n.'.json'),true);$rows=[];$courses=['101'=>'hotel','301'=>'airline','600'=>'railway','820'=>'international'];
 foreach($ref as $field)if(($field['name']??'')==='st_pg')foreach($field['options'] as $option)if($option['value'])$rows[]=['id'=>wp_generate_uuid4(),'course'=>$courses[explode('-',$option['value'])[0]]??'','label'=>$option['text'],'description'=>''];
 update_post_meta($id,'_ibc_programs',$rows);update_option('ibc_demo_reference_'.$n,$id);
}
echo wp_json_encode(['form'=>$form->id(),'page'=>$page,'demo_595'=>get_option('ibc_demo_reference_595'),'demo_592'=>get_option('ibc_demo_reference_592')])."\n";
