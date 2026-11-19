<?php
if(!defined('ABSPATH'))exit;
function ibc_inquiry_kind($form=null){
 if(!$form && class_exists('WPCF7_ContactForm'))$form=WPCF7_ContactForm::get_current();
 if(!$form)return '';
 foreach(['req','consul'] as $kind)if((int)$form->id()===(int)get_option('ibc_'.$kind.'_form'))return $kind;
 return '';
}
function ibc_inquiry_errors($data,$kind){
 // Reuse the same personal-data rules as the OC form, without OC-specific checks.
 $errors=ibc_reg_validation_errors($data,0);unset($errors['program']);
 if($kind==='consul'){
  unset($errors['birth-day'],$errors['zip1'],$errors['zip2']);
  $value=$data['consultation-date']??'';
  if($value!==''){
   $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value,new DateTimeZone('Asia/Tokyo'));
   if(!$date||$date->format('Y-m-d')!==$value)$errors['consultation-date']='正しい希望日を選んでください。';
   elseif($date<new DateTimeImmutable('today',new DateTimeZone('Asia/Tokyo')))$errors['consultation-date']='希望日は今日以降の日付を選んでください。';
  }
 }
 return $errors;
}
add_filter('wpcf7_validate',function($result,$tags){
 $kind=ibc_inquiry_kind();if(!$kind)return $result;
 $data=[];foreach(['your-name','kana','birth-year','birth-month','birth-day','zip1','zip2','phone','consultation-date'] as $key)$data[$key]=ibc_reg_posted($key);
 $errors=ibc_inquiry_errors($data,$kind);
 foreach($tags as $tag)if(isset($errors[$tag->name]))$result->invalidate($tag,$errors[$tag->name]);
 return $result;
},30,2);
add_filter('wpcf7_skip_mail',function($skip,$form){return ibc_inquiry_kind($form)?true:$skip;},999,2);
add_shortcode('ibc_inquiry',function($attrs){
 $kind=($attrs['kind']??'')==='consul'?'consul':'req';$id=absint(get_option('ibc_'.$kind.'_form'));
 if(!$id||!function_exists('wpcf7_contact_form'))return '<p>フォームを準備中です。</p>';
 $title=$kind==='consul'?'個別相談':'資料請求';
 return '<section class="ibc-entry" data-kind="'.$kind.'" data-return="/" data-return-label="トップページへ戻る" data-complete="'.esc_attr($title.'デモが完了しました').'"><p class="status">'.$title.'のデモです。架空の情報でお試しください。実際の受付・メール送信・入力内容の保存は行いません。</p><ol class="entry-steps"><li aria-current="step">1 ご入力</li><li>2 ご確認</li><li>3 完了</li></ol>'.do_shortcode('[contact-form-7 id="'.$id.'"]').'</section>';
});
