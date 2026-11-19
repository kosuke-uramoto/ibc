<?php
if(PHP_SAPI!=='cli'||!defined('ABSPATH'))exit;
$categories=[];
foreach(['news'=>'NEWS（お知らせ）','campus-life'=>'CAMPUS LIFE（学校生活）'] as $slug=>$label){
 $term=get_term_by('slug',$slug,'category');
 if(!$term){$result=wp_insert_term($label,'category',['slug'=>$slug]);if(is_wp_error($result))throw new RuntimeException($result->get_error_message());$categories[$slug]=$result['term_id'];}else $categories[$slug]=$term->term_id;
}
update_option('default_category',$categories['news']);
$summaries=[
15168=>'都城・鹿児島・鹿屋・熊本・宮崎で開催する出張型オープンキャンパスの案内です。学校や学科の紹介、業界体験を通じて、身近な会場で進路について相談できます。開催会場・日時や申し込み方法は現行サイトの記事をご確認ください。',
15925=>'2026年10月28日時点の留学生向け願書受付に関する案内です。国際ホテルマネジメント科などの受付状況と、対象学科への出願に必要な日本語能力の要件が紹介されています。最新の受付状況は学校へご確認ください。',
15889=>'ITビジネス科について、2027年度以降の学生募集を停止する方針が公表されています。在学生の卒業まで教育環境と支援を継続し、卒業生にも対応する旨が案内されています。',
15905=>'タイの9県15校から教職員と生徒が来校し、IBCの学生たちと交流しました。伝統舞踊の鑑賞や日本の昔遊びを通じて、互いの文化に触れる機会となりました。',
15519=>'高校1・2年生の段階からオープンキャンパスを活用する進路選びを紹介しています。複数分野を比較する時間を持ち、入試制度や仕事への理解を早めに深めることが勧められています。'
];
$rows=json_decode(file_get_contents(dirname(ABSPATH).'/docs/news-campus-import.json'),true);
foreach($rows as $row){
 parse_str(parse_url($row['source'],PHP_URL_QUERY),$query);$source_id=(int)$query['p'];
 $existing=get_posts(['post_type'=>'post','post_status'=>'any','meta_key'=>'_ibc_source_url','meta_value'=>$row['source'],'posts_per_page'=>1]);
 if($existing){echo 'Existing '.$existing[0]->ID."\n";continue;}
 $content='<p class="source-note">現行サイトの記事をもとにした、表示確認用の要約です。</p><p>'.esc_html($summaries[$source_id]).'</p><p><a href="'.esc_url($row['source']).'" target="_blank" rel="noopener">現行サイトの記事を読む ↗</a></p>';
 $date=$row['date'].' 00:00:00';
 $id=wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>$row['title'],'post_content'=>$content,'post_date'=>$date,'post_date_gmt'=>get_gmt_from_date($date),'post_category'=>array_map(fn($slug)=>(int)$categories[$slug],$row['categories'])],true);
 if(is_wp_error($id))throw new RuntimeException($id->get_error_message());update_post_meta($id,'_ibc_source_url',$row['source']);echo 'Created '.$id.' '.$row['title']."\n";
}
foreach(['news','campus-life'] as $category)echo $category.': '.count(ibc_editorial_feed($category))." posts\n";
