<?php
class Scores {
  function Scores() {
    $settings = admin_settings::get_settings();
    $this->esteemScores = array('L' => $settings->esteem_like,
				'C' => $settings->esteem_comment,
				'S' => $settings->esteem_share,
				'V' => $settings->esteem_view,
				'F' => $settings->esteem_follow);
    $this->engagementScores = array('L' => $settings->engagement_like,
				    'C' => $settings->engagement_comment,
				    'S' => $settings->engagement_share,
				    'V' => $settings->engagement_view,
				    'F' => $settings->engagement_follow);
    $this->esteem = array();
    $this->engagement = array();
    
    Scores::update($this->engagement, "
select user_id, count(*) as n
 from user_likes_tt l
 inner join teachingtip t on l.teachingtip_id = t.id
 where t.author_id <> l.user_id and t.status = 'active'
 group by user_id", 'L');
    Scores::update($this->engagement, "
select user_id, count(*) as n
 from user_comments_tt c
 inner join teachingtip t on c.teachingtip_id = t.id
 where t.author_id <> c.user_id and t.status = 'active'
 group by user_id", 'C');
    Scores::update($this->engagement, "
select user_id, count(*) as n
 from ttview v
 inner join teachingtip t on v.teachingtip_id = t.id
 where t.author_id <> v.user_id and t.status = 'active'
 group by user_id", 'V');
    Scores::update($this->engagement, "
select follower_id as user_id, count(*) as n
 from user_follows_user
 group by follower_id", 'F');
    Scores::update($this->engagement, "
select u.id as user_id, count(*) as n
 from user_shares_tt s
 inner join user u on s.sender = u.email
 inner join teachingtip t on s.teachingtip_id = t.id
 where t.author_id <> u.id and t.status = 'active'
 group by sender", 'S');
    
    Scores::update($this->esteem, "
select t.author_id as user_id, count(*) as n
 from user_likes_tt l
 inner join teachingtip t on l.teachingtip_id = t.id
 where l.user_id <> t.author_id and t.status = 'active'
 group by t.author_id", 'L');
    Scores::update($this->esteem, "
select t.author_id as user_id, count(*) as n
 from user_comments_tt c
 inner join teachingtip t on c.teachingtip_id = t.id
 where c.user_id <> t.author_id and t.status = 'active'
 group by t.author_id", 'C');
    Scores::update($this->esteem, "
select t.author_id as user_id, count(*) as n
 from ttview v
 inner join teachingtip t on v.teachingtip_id = t.id
 where v.user_id <> t.author_id and t.status = 'active'
 group by t.author_id", 'V');
    Scores::update($this->esteem, "
select user_id, count(*) as n
 from user_follows_user
 group by user_id", 'F');
    Scores::update($this->esteem, "
select t.author_id as user_id, count(*) as n
 from user_shares_tt s
 inner join user u on s.sender = u.email
 inner join teachingtip t on s.teachingtip_id = t.id
 where u.id <> t.author_id and t.status = 'active'
 group by t.author_id", 'S');
  }

  function update(&$data, $query, $cat) {
    foreach (dataConnection::runQuery($query) as $row) {
      $user_id = $row['user_id'];
      if (!isset($data[$user_id])) $data[$user_id] = array();
      $data[$user_id][$cat] = $row['n'];
    }
  }
  
  function engagementScore($user_id) { return Scores::score($this->engagement, $user_id, $this->engagementScores); }

  function esteemScore($user_id) { return Scores::score($this->esteem, $user_id, $this->esteemScores); }

  function showEsteem($user_id) { return Scores::show($this->esteem, $user_id, $this->esteemScores); }

  function showEngagement($user_id) { return Scores::show($this->engagement, $user_id, $this->engagementScores); }
  
  static function score($data, $user_id, $weights) {
    $score = 0;
    if (isset($data[$user_id]))
      foreach ($data[$user_id] as $cat => $n)
	$score += $n * $weights[$cat];
    return $score;
  }
  
  static function show($data, $user_id, $weights) {
    $res = array();
    if (isset($data[$user_id]))
      foreach ($data[$user_id] as $cat => $n)
	$res[] = $cat . 'x' . $n;
    
    return join(" + ", $res) . ' = ' . Scores::score($data, $user_id, $weights);
  }
}
