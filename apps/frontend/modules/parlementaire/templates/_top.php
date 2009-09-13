<?php
$titres = array('semaine' => 'Semaines',
	       'commission_presences' => 'Présences en commission',
	       'commission_interventions'=> 'Interventions en commission',
	       'hemicycle_interventions'=>'Interventions longues en hémicycle',
	       'hemicycle_invectives'=>'Interventions courtes en hémicycle',
	       'amendements_signes' => 'Amendements signés',
		//	       'amendements_adoptes'=>'Amendements adoptés',
		//	       'amendements_rejetes' => 'Amendements rejetés',
	       'questions_ecrites' => 'Questions écrites',
	       'questions_orales' => 'Questions orales');
$images = array('semaine' => 'ico_sem_%s.png',
	       'commission_presences' => 'ico_com_pre_%s.png',
	       'commission_interventions'=> 'ico_com_inter_%s.png',
	       'hemicycle_interventions'=>'ico_inter_hem_long_%s.png',
	       'hemicycle_invectives'=>'ico_inter_hem_court_%s.png',
	       'amendements_signes' => 'ico_amendmt_sign_%s.png',
		//	       'amendements_adoptes'=>'ico_amendmt_ado_%s.png',
		//	       'amendements_rejetes' => 'ico_amendmt_ref_%s.png',
	       'questions_ecrites' => 'ico_quest_ecrit_%s.png',
	       'questions_orales' => 'ico_quest_oral_%s.png');
$sort = array('semaine' => '1',
	       'commission_presences' => '2',
	       'commission_interventions'=> '3',
	       'hemicycle_interventions'=>'4',
	       'hemicycle_invectives'=>'5',
	       'amendements_signes' => '6',
		'amendements_adoptes'=>'7',
		'amendements_rejetes' => '8',
	       'questions_ecrites' => '9',
	       'questions_orales' => '10');
$couleur2style = array('vert' => ' style="color: green"',
	       'gris' => '',
	       'rouge' => ' style="color: red"');
$top = $parlementaire->getTop();
if (!$top)
  return ;
if (!$parlementaire->fin_mandat) {
  echo '<h2>Activité parlementaire <small>(12 derniers mois) :</small></h3>';
  $rank = 1;
 } else {
  $rank = 0;
  $weeks = (strtotime($parlementaire->fin_mandat) - strtotime($parlementaire->debut_mandat))/(60*60*24*7);
  echo '<h2>';
if ($parlementaire->fin_mandat) 
{
  echo "<strong>Mandat clos</strong> ";
 }
 printf('Bilan de ses %d semaines de mandat :</h2>', $weeks);
 }
?>
<ul><?php
foreach(array_keys($images) as $k) {
  $couleur = 'gris';
  if ($rank && $top[$k]['rank'] <= 150) 
    $couleur = 'vert';
  else if ($rank && $top[$k]['rank'] >= $top[$k]['max_rank'] - 150) 
    $couleur = 'rouge';
  echo '<li';
  echo $couleur2style[$couleur];
  echo'>';
  if ($rank)
    echo '<a href="'.url_for('@top_global_sorted?sort='.$sort[$k].'#'.$parlementaire->slug).'">';
  echo '<img src="/images/xneth/';
  printf($images[$k], $couleur);
  echo '" alt="'.$titres[$k].'">';
  echo ' : ';
  if (isset($top[$k]['value']))
    echo $top[$k]['value'];
  else echo '0';
  if ($rank)
    echo '</a>';
  echo '</li>';
}?></ul>