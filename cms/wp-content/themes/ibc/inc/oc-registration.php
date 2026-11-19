<?php
/** Local demonstration only. No application email is sent. */
if (!defined('ABSPATH')) exit;
function ibc_reg_programs($id) {
 $rows=get_post_meta($id,'_ibc_programs',true);
 if(is_array($rows)) return $rows;
 $rows=[];
 foreach(ibc_courses() as $key=>$label) {
  $text=get_post_meta($id,'_ibc_'.$key,true);
  if($text!=='') $rows[]=['id'=>$key,'course'=>$key,'label'=>$text,'description'=>''];
 }
 return $rows;
}
function ibc_reg_event_id() {
 if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST' && is_singular('ibc_oc'))return (int)get_queried_object_id();
 $value=($_SERVER['REQUEST_METHOD']??'GET')==='POST'?($_POST['oc_event']??''):($_GET['event']??'');
 return is_scalar($value)?absint($value):0;
}
function ibc_reg_available($id) {
 $event=ibc_event($id);
 if(!$event || $event['state']!=='open' || get_post_meta($id,'_ibc_registration_enabled',true)==='0' || !ibc_reg_programs($id)) return false;
 $limit=ibc_datetime(get_post_meta($id,'_ibc_deadline',true));
 return time()<min(strtotime($event['start']),$limit?$limit->getTimestamp():PHP_INT_MAX);
}
function ibc_reg_revision($id) {
 return hash('sha256',wp_json_encode([ibc_reg_programs($id),get_post_meta($id,'_ibc_start',true),get_post_meta($id,'_ibc_end',true),get_post_meta($id,'_ibc_interview',true)]));
}
function ibc_reg_url($id) {
 return get_post_type($id)==='ibc_oc'?get_permalink($id).'#oc-application':'';
}
function ibc_reg_is_form($form=null) {
 if(!$form && class_exists('WPCF7_ContactForm')) $form=WPCF7_ContactForm::get_current();
 return $form && (int)$form->id()===(int)get_option('ibc_registration_form');
}
add_action('add_meta_boxes',function(){add_meta_box('ibc_registration','申込プログラム・受付設定','ibc_reg_admin','ibc_oc','normal','high');});
function ibc_reg_admin($post) {
 wp_nonce_field('ibc_registration','ibc_registration_nonce');
 echo '<p>ここに登録したプログラムが、OC紹介と申込フォームの選択肢に反映されます。CF7の編集は不要です。来校・オンラインを分ける場合は、それぞれ1行追加してください。</p>';
 echo '<p><label><input type="checkbox" name="ibc_reg_enabled" value="1" '.checked(get_post_meta($post->ID,'_ibc_registration_enabled',true)!=='0',true,false).'> 申込デモを受け付ける</label></p>';
 echo '<p><label>受付締切（日本時間） <input type="datetime-local" name="ibc_reg_deadline" value="'.esc_attr(get_post_meta($post->ID,'_ibc_deadline',true)).'"></label><br>空欄の場合は開催開始時刻に締め切ります。</p>';
 echo '<p><label><input type="checkbox" name="ibc_reg_interview" value="1" '.checked(get_post_meta($post->ID,'_ibc_interview',true),'1',false).'> 総合型選抜面談の希望確認を表示する（高校3年・大学短大生・社会人のみ）</label></p>';
 echo '<div id="ibc-program-rows">';foreach(ibc_reg_programs($post->ID) as $i=>$row) ibc_reg_admin_row($i,$row);echo '</div>';
 echo '<template id="ibc-program-template">';ibc_reg_admin_row('__INDEX__',['id'=>'','course'=>'','label'=>'','description'=>'']);echo '</template><p><button type="button" class="button" id="ibc-add-program">プログラムを追加</button></p><p>削除・変更後は「更新」を押してください。既存の学科別体験欄より、この欄の内容が優先されます。</p>';
}
function ibc_reg_admin_row($i,$row) {
 echo '<div class="ibc-program-row" style="padding:16px;margin-bottom:12px;border:1px solid #ccd0d4"><input type="hidden" name="ibc_programs['.esc_attr($i).'][id]" value="'.esc_attr($row['id']).'"><p><label>掲載する学科 <select name="ibc_programs['.esc_attr($i).'][course]"><option value="">全学科共通</option>';
 foreach(ibc_courses() as $key=>$label) echo '<option value="'.esc_attr($key).'" '.selected($row['course'],$key,false).'>'.esc_html($label).'</option>';
 echo '</select></label></p><p><label>プログラム名（例：ホテル体験／来校参加）<br><input style="width:100%" type="text" name="ibc_programs['.esc_attr($i).'][label]" value="'.esc_attr($row['label']).'"></label></p><p><label>紹介文（任意）<br><textarea style="width:100%" name="ibc_programs['.esc_attr($i).'][description]">'.esc_textarea($row['description']).'</textarea></label></p><button type="button" class="button ibc-remove-program">このプログラムを削除</button></div>';
}
add_action('admin_enqueue_scripts',function(){ $screen=get_current_screen(); if($screen && $screen->post_type==='ibc_oc') wp_enqueue_script('ibc-program-admin',get_theme_file_uri('/inc/program-admin.js'),[],filemtime(__DIR__.'/program-admin.js'),true); });
add_action('save_post_ibc_oc',function($id){
 if(wp_is_post_revision($id) || (defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE) || !current_user_can('edit_post',$id) || !isset($_POST['ibc_registration_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ibc_registration_nonce'])),'ibc_registration')) return;
 $rows=[];$seen=[];
 foreach((array)($_POST['ibc_programs']??[]) as $row){
  if(!is_array($row)||!isset($row['label'])||!is_string($row['label']))continue;
  $label=sanitize_text_field(wp_unslash($row['label']));if($label==='')continue;
  $key=isset($row['id'])&&is_string($row['id'])?sanitize_key($row['id']):'';
  if(!$key||isset($seen[$key]))$key=wp_generate_uuid4();$seen[$key]=true;
  $course=isset($row['course'])&&is_string($row['course'])?sanitize_key($row['course']):'';
  $description=isset($row['description'])&&is_string($row['description'])?sanitize_textarea_field(wp_unslash($row['description'])):'';
  $rows[]=['id'=>$key,'course'=>array_key_exists($course,ibc_courses())?$course:'','label'=>$label,'description'=>$description];
 }
 update_post_meta($id,'_ibc_programs',$rows);
 update_post_meta($id,'_ibc_registration_enabled',isset($_POST['ibc_reg_enabled'])?'1':'0');
 update_post_meta($id,'_ibc_interview',isset($_POST['ibc_reg_interview'])?'1':'0');
 $deadline=isset($_POST['ibc_reg_deadline'])&&is_string($_POST['ibc_reg_deadline'])?sanitize_text_field(wp_unslash($_POST['ibc_reg_deadline'])):'';
 update_post_meta($id,'_ibc_deadline',ibc_datetime($deadline)?$deadline:'');
});
add_action('wpcf7_init',function(){
 wpcf7_add_form_tag('ibcprogram*','ibc_reg_program_tag',['name-attr'=>true]);
 wpcf7_add_form_tag('ibcevent','ibc_reg_event_tag',['name-attr'=>true]);
 wpcf7_add_form_tag('ibcinterview','ibc_reg_interview_tag',['name-attr'=>true]);
});
function ibc_reg_program_tag($tag){
 $html='<span class="wpcf7-form-control-wrap" data-name="program"><select name="program" id="ibc-program" class="wpcf7-form-control" required aria-required="true"><option value="">プログラムを選択してください。</option>';
 foreach(ibc_reg_programs(ibc_reg_event_id()) as $row)$html.='<option value="'.esc_attr($row['id']).'">'.esc_html($row['label']).'</option>';
 return $html.'</select></span>';
}
function ibc_reg_event_tag($tag){$id=ibc_reg_event_id();return '<input type="hidden" name="oc_event" value="'.esc_attr($id).'"><input type="hidden" name="oc_revision" value="'.esc_attr(ibc_reg_revision($id)).'">';}
function ibc_reg_interview_tag($tag){
 if(get_post_meta(ibc_reg_event_id(),'_ibc_interview',true)!=='1')return '';
 $html='<fieldset class="ibc-interview" data-field-label="総合型選抜面談の希望確認"><legend>総合型選抜面談の希望確認 <small>必須</small></legend><span class="wpcf7-form-control-wrap" data-name="interview">';
 foreach(['希望する','検討中','希望しない'] as $label)$html.='<label class="choice"><input type="radio" name="interview" value="'.esc_attr($label).'">'.esc_html($label).'</label>';
 return $html.'</span></fieldset>';
}
function ibc_reg_posted($key){$v=$_POST[$key]??'';return is_string($v)?sanitize_text_field(wp_unslash($v)):'';}
function ibc_reg_validation_errors($data,$id){
 $errors=[];
 if(!ibc_reg_available($id))$errors['program']='この開催回は受付を終了したか、現在お申し込みできません。';
 elseif(!hash_equals(ibc_reg_revision($id),(string)($data['oc_revision']??'')))$errors['program']='開催情報が変更されました。ページを再読み込みして選び直してください。';
 elseif(!in_array($data['program']??'',array_column(ibc_reg_programs($id),'id'),true))$errors['program']='この開催回のプログラムを1つ選んでください。';
 if(!preg_match('/^[^\x00-\x7F]+[ 　]+[^\x00-\x7F]+$/u',$data['your-name']??''))$errors['your-name']='氏名は全角で、姓と名の間にスペースを入れてください。';
 if(!preg_match('/^[ァ-ヶー・]+[ 　]+[ァ-ヶー・]+$/u',$data['kana']??''))$errors['kana']='フリガナを全角カタカナで、姓と名の間にスペースを入れてください。';
 if(!checkdate((int)($data['birth-month']??0),(int)($data['birth-day']??0),(int)($data['birth-year']??0)))$errors['birth-day']='正しい生年月日を選んでください。';
 foreach(['zip1'=>3,'zip2'=>4] as $key=>$length)if(!preg_match('/^[0-9]{'.$length.'}$/',$data[$key]??''))$errors[$key]='半角数字'.$length.'桁で入力してください。';
 if(!preg_match('/^[0-9()+ -]{10,20}$/',$data['phone']??''))$errors['phone']='電話番号を半角数字で入力してください。';
 if(get_post_meta($id,'_ibc_interview',true)==='1'&&in_array($data['grade']??'',['高校３年','大学 ・ 短大生','社会人'],true)&&!in_array($data['interview']??'',['希望する','検討中','希望しない'],true))$errors['interview']='面談の希望を選択してください。';
 return $errors;
}
add_filter('wpcf7_validate',function($result,$tags){
 if(!ibc_reg_is_form())return $result;
 $data=[];foreach(['oc_revision','program','your-name','kana','birth-year','birth-month','birth-day','zip1','zip2','phone','grade','interview'] as $key)$data[$key]=ibc_reg_posted($key);
 $errors=ibc_reg_validation_errors($data,absint(ibc_reg_posted('oc_event')));
 foreach($tags as $tag)if(isset($errors[$tag->name]))$result->invalidate($tag,$errors[$tag->name]);
 return $result;
},30,2);
// Demo delivery is blocked even if someone edits the CF7 additional settings.
add_filter('wpcf7_skip_mail',function($skip,$form){return ibc_reg_is_form($form)?true:$skip;},999,2);
add_filter('wpcf7_submission_result',function($result,$submission){if(ibc_reg_is_form()&&($result['status']??'')==='mail_sent')$result['message']='申込デモが完了しました。実際の申し込み・メール送信は行われていません。';return $result;},10,2);
add_shortcode('ibc_oc_entry',function(){
 $id=ibc_reg_event_id();$event=ibc_event($id);
 if(!$event)return '<p class="status">開催回が見つかりません。OC一覧から選んでください。</p><a href="/oc/">OC一覧へ</a>';
 if(!ibc_reg_available($id))return '<p class="status">この開催回は現在お申し込みできません。</p><a href="'.esc_url($event['url']).'">開催内容へ戻る</a>';
 if(!function_exists('wpcf7_contact_form'))return '<p>フォームを準備中です。</p>';
 $form=absint(get_option('ibc_registration_form'));if(!$form)return '<p>フォームを準備中です。</p>';
 $out='<section id="oc-application" class="ibc-entry" data-complete="OC申込デモが完了しました" data-event="'.esc_attr($id).'"><p class="status">申込デモです。架空の情報でお試しください。メール送信・申込情報の保存は行いません。</p><h2>'.esc_html($event['title']).'</h2><p>'.esc_html(wp_date('Y年n月j日 H:i',strtotime($event['start']),new DateTimeZone('Asia/Tokyo')).' ／ '.$event['session']).'</p><ol class="entry-steps"><li aria-current="step">1 ご入力</li><li>2 ご確認</li><li>3 完了</li></ol>';
 return $out.do_shortcode('[contact-form-7 id="'.$form.'"]').'</section>';
});
add_action('wp_enqueue_scripts',function(){
 if(is_singular('ibc_oc') || is_page(absint(get_option('ibc_registration_page'))) || is_page([absint(get_option('ibc_req_page')),absint(get_option('ibc_consul_page'))])){
  nocache_headers();
  wp_enqueue_style('ibc-entry',get_theme_file_uri('/inc/entry.css'),[],filemtime(__DIR__.'/entry.css'));
  wp_enqueue_script('ibc-entry',get_theme_file_uri('/inc/entry.js'),[],filemtime(__DIR__.'/entry.js'),true);
 }
});

// Preserve old entry-page links while shortening the normal journey.
add_action('template_redirect',function(){
 if(is_page(absint(get_option('ibc_registration_page'))) && ibc_event(ibc_reg_event_id())) {
  wp_safe_redirect(ibc_reg_url(ibc_reg_event_id()),302); exit;
 }
});
