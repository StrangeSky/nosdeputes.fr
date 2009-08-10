<?php

class setVacancesTask extends sfBaseTask {
  protected function configure() {
    $this->namespace = 'set';
    $this->name = 'Vacances';
    $this->briefDescription = 'Load Vacances from Seances';
  }

  protected function execute($arguments = array(), $options = array()) {
    $semaines = array();

    $manager = new sfDatabaseManager($this->configuration);
    $q = Doctrine_Query::create()->select('s.annee, s.numero_semaine')
      ->from('Seance s')
      ->groupBy('s.annee, s.numero_semaine')
      ->orderBy('s.date ASC');
    $seances = $q->fetchArray();

    $annee = $seances[0]['annee'];
    $sem = $seances[0]['numero_semaine'];
    foreach ($seances as $seance) {
      while (($annee < $seance['annee']) || ($annee == $seance['annee'] && $sem < $seance['numero_semaine'])) {
        array_push($semaines, array("annee" => $annee, "semaine" => $sem));
        if ($sem == 52) { $annee++; $sem = 1; }
        else $sem++;
      }
      if ($seance['numero_semaine'] == 52) { $annee = $seance['annee'] + 1 ; $sem = 1; }
      else { $annee = $seance['annee']; $sem = $seance['numero_semaine'] + 1; }
    }

    $date = time();
    $last_annee = date('Y', $date);
    $last_sem = date('W', $date);
    if ($last_sem == 53) { $last_annee++; $last_sem = 1; }
    while (($annee < $last_annee) || ($annee == $last_annee && $sem <= $last_sem)) {
      array_push($semaines, array("annee" => $annee, "semaine" => $sem));
      if ($sem == 52) { $annee++; $sem = 1; }
      else $sem++;
    }

    $option = Doctrine::getTable('VariableGlobale')->findOneByChamp('vacances');
    if (!$option) {
      $option = new VariableGlobale();
      $option->setChamp('vacances');
      $option->setValue(serialize($semaines));
    } else $option->value = serialize($semaines);
    $option->save();
    $option->free();
    $option = Doctrine::getTable('VariableGlobale')->findOneByChamp('vacances');
    echo unserialize($option);
  }
}

?>