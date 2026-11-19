<?php
if(PHP_SAPI!=='cli'||!defined('ABSPATH'))exit;
foreach(['req'=>'資料請求','consul'=>'来校個別相談・オンライン個別相談'] as $kind=>$title){
 $id=(int)get_option('ibc_'.$kind.'_form');$form=$id?wpcf7_contact_form($id):null;
 if(!$form){$form=WPCF7_ContactForm::get_template(['locale'=>'ja']);$form->set_title('IBC '.$title.'（送信しないデモ）');}
 $props=$form->get_properties();$props['form']=file_get_contents(__DIR__.'/'.$kind.'-form.txt');$props['additional_settings']="skip_mail: on\ndo_not_store: true";
 $props['messages']['mail_sent_ok']=$title.'のデモが完了しました。実際の受付・メール送信は行っていません。';
 $props['mail']['recipient']='demo@example.com';$props['mail']['sender']='IBC Demo <demo@example.com>';$props['mail']['subject']='【デモ】'.$title;$props['mail']['body']="氏名: [your-name]\nメール: [your-email]";$props['mail']['additional_headers']='';$props['mail_2']['active']=false;
 $form->set_properties($props);$form->save();update_option('ibc_'.$kind.'_form',$form->id());
 $page=(int)get_option('ibc_'.$kind.'_page');
 if(!$page||!get_post($page)){
  $page=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$title,'post_name'=>$kind,'post_content'=>'[ibc_inquiry kind="'.$kind.'"]'],true);
  if(is_wp_error($page))throw new RuntimeException($page->get_error_message());update_option('ibc_'.$kind.'_page',$page);
 }
 echo $kind.' form='.$form->id().' page='.$page.' '.get_permalink($page)."\n";
}
