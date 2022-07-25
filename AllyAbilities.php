<?php

function PlayAlly($cardID, $player, $subCards="-")
{
  $allies = &GetAllies($player);
  array_push($allies, $cardID);
  array_push($allies, 2);
  array_push($allies, AllyHealth($cardID));
  array_push($allies, 0);//Frozen
  array_push($allies, $subCards);//Subcards
  array_push($allies, GetUniqueId());//Unique ID
  array_push($allies, AllyEnduranceCounters($cardID));//Endurance Counters
  array_push($allies, 0);//Life Counters
  array_push($allies, 1);//Ability/effect uses
  if($cardID == "UPR414") { WriteLog("Ouvia lets you transform an ashling."); Transform($player, "Ash", "UPR042", true); }
  return count($allies) - AllyPieces();
}

function DestroyAlly($player, $index, $skipDestroy=false)
{
  $allies = &GetAllies($player);
  if(!$skipDestroy)
  {
    AllyDestroyedAbility($player, $index);
  }
  if(IsSpecificAllyAttacking($player, $index)) { CloseCombatChain(); }
  if(DelimStringContains(CardSubType($allies[$index]), "Dragon"))
  {
    $set = substr($allies[$index], 0, 3);
    $number = intval(substr($allies[$index], -3));
    $number -= 400;
    $id = $number;
    if($number < 100) $id = "0" . $id;
    if($number < 10) $id = "0" . $id;
    $id = $set . $id;
    AddGraveyard($id, $player, "PLAY");
  }
  for($j = $index+AllyPieces()-1; $j >= $index; --$j)
  {
    unset($allies[$j]);
  }
  $allies = array_values($allies);
}

function AllyHealth($cardID)
{
  switch($cardID)
  {
    case "MON219": return 6;
    case "MON220": return 6;
    case "UPR406": return 6;
    case "UPR407": return 5;
    case "UPR408": return 4;
    case "UPR409": return 3;
    case "UPR410": return 2;
    case "UPR411": return 2;
    case "UPR412": return 4;
    case "UPR413": return 7;
    case "UPR414": return 6;
    case "UPR415": return 4;
    case "UPR416": return 1;
    case "UPR417": return 3;
    default: return 1;
  }
}

function AllyDestroyedAbility($player, $index)
{
  global $mainPlayer;
  $allies = &GetAllies($player);
  $cardID = $allies[$index];
  switch($cardID)
  {
    case "UPR410":
      if($player == $mainPlayer && $allies[$i+8] > 0)
      {
        GainActionPoints(1);
        WriteLog("Cromai leaves the arena. Gain 1 action point.");
        --$allies[$i+8];
      }
      break;
    case "UPR551":
      $gtIndex = FindCharacterIndex($player, "UPR151");
      if($gtIndex > -1)
      {
        DestroyCharacter($player, $gtIndex);
      }
      break;
    default: break;
  }
}

function AllyStartTurnAbilities($player)
{
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "UPR414": WriteLog("Ouvia lets you transform an ashling."); Transform($player, "Ash", "UPR042", true); break;
      default: break;
    }
  }
}

function AllyEnduranceCounters($cardID)
{
  switch($cardID)
  {
    case "UPR417": return 1;
    default: return 0;
  }
}

function AllyDamagePrevention($player, $index, $damage)
{
  $allies = &GetAllies($player);
  $cardID = $allies[$index];
  switch($cardID)
  {
    case "UPR417":
      if($allies[$index+6] > 0)
      {
        $damage -= 3;
        if($damage < 0) $damage = 0;
        --$allies[$index+6];
      }
      return $damage;
    default: return $damage;
  }
}

function AllyAttackAbilities($attackID)
{
  global $mainPlayer;
  $allies = &GetAllies($mainPlayer);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "UPR412":
        if($allies[$i+8] > 0 && DelimStringContains(CardSubType($attackID), "Dragon"))
        {
          AddCurrentTurnEffect("UPR412", $mainPlayer);
          --$allies[$i+8];
        }
        break;
      default: break;
    }
  }
}

function SpecificAllyAttackAbilities($attackID)
{
  global $mainPlayer, $combatChainState, $CCS_WeaponIndex;
  $allies = &GetAllies($mainPlayer);
  $i = $combatChainState[$CCS_WeaponIndex];
  switch($allies[$i])
  {
    case "UPR410":
      if($attackID == $allies[$i] && $allies[$i+8] > 0)
      {
        GainActionPoints(1);
        WriteLog("Cromai Attacks: Gain 1 action point.");
        --$allies[$i+8];
      }
      break;
    default: break;
  }
}

function AllyDamageTakenAbilities($player, $index)
{
  $allies = &GetAllies($player);
  switch($allies[$index])
  {
    case "UPR413":
      $allies[$index+2] -= 1;
      $allies[$index+7] -= 1;
      PutPermanentIntoPlay($player, "UPR043");
      WriteLog("Nekria got a -1 health counter and created an ash token.");
      break;
    default: break;
  }
}

function AllyBeginEndPhaseEffects()
{
  global $mainPlayer, $defPlayer;
  //CR 2.0 4.4.3a Reset health for all allies
  $mainAllies = &GetAllies($mainPlayer);
  for($i=0; $i<count($mainAllies); $i+=AllyPieces())
  {
    if($mainAllies[$i+1] != 0) {
      $mainAllies[$i+1] = 2;
      $mainAllies[$i+2] = AllyHealth($mainAllies[$i]) + $mainAllies[$i+7];
      $mainAllies[$i+8] = 1;
    }
  }

  $defAllies = &GetAllies($defPlayer);
  for($i=0; $i<count($defAllies); $i+=AllyPieces())
  {
    if($defAllies[$i+1] != 0) {
      $defAllies[$i+1] = 2;
      $defAllies[$i+2] = AllyHealth($defAllies[$i]) + $defAllies[$i+7];
      $defAllies[$i+8] = 1;
    }
  }
}

function AllyEndTurnAbilities()
{
  global $mainPlayer;
  $allies = &GetAllies($mainPlayer);
  for($i=count($allies)-AllyPieces(); $i>=0; $i-=AllyPieces())
  {
    switch($allies[$i])
    {
      case "UPR551": DestroyAlly($mainPlayer, $i, true); break;
      default: break;
    }
  }
}

?>
