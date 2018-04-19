<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Cliff Parnitzky 2015-2015
 * @author     Cliff Parnitzky
 * @package    RscDataExporterCollmex
 * @license    LGPL
 */

/**
 * Class RscDataExporterCollmex
 *
 * The exporter of member data for Collmex.
 * @copyright  Cliff Parnitzky 2012
 * @author     Cliff Parnitzky
 * @package    Controller
 */
class RscDataExporterCollmex extends AbstractDataExporter
{
	/**
	 * Create the export file
	 */
	public function createExportFile($objConfig)
	{
		$objFile = $this->createFile($objConfig, "Mitgliederexport_fuer_Collmex_" . date("Y_m_d"), 'csv');
		
		$objFile->append("Typkennung;Mitglied Nr;Anrede;Titel;Vorname;Name;Firma;Abteilung;Straße;PLZ;Ort;Reserviert;Reserviert;Land;Telefon;Telefax;E-Mail;KontoNr;BLZ;IBAN;BIC;Bankname;Sepa-Mandatsreferenz;Datum Unterschrift;Geburtsdatum;Eintrittsdatum;Austrittsdatum;Bemerkung;Telefon2;Skype/VoIP;Inhaber;Ausgabemedium;Adressgruppe;Zahlungsbed.;Abrechnung über;Ausgabe-Sprache;Kostenstelle");
		
		$this->import("Database");
		$dbResult = $this->Database->prepare("SELECT * FROM tl_member m JOIN tl_member_to_group m2g ON m2g.member_id = m.id WHERE m2g.group_id = 7 OR m2g.group_id = 13 ORDER BY xt_club_membernumber")
										 ->execute();

		while ($dbResult->next())
		{
			$arrRowData = array();
			$arrRowData[] = "CMXMGD"; // Typkennung (FIX)
			$arrRowData[] = $this->getMemberNumber($dbResult->xt_club_membernumber); // Mitglied Nr
			$arrRowData[] = $this->getTitel($dbResult->gender);; // Anrede
			$arrRowData[] = ""; // Titel
			$arrRowData[] = utf8_decode($dbResult->firstname); // Vorname
			$arrRowData[] = utf8_decode($dbResult->lastname); // Name
			$arrRowData[] = ""; // Firma
			$arrRowData[] = ""; // Abteilung
			$arrRowData[] = utf8_decode($dbResult->street); // Straße
			$arrRowData[] = $dbResult->postal; // PLZ
			$arrRowData[] = utf8_decode($dbResult->city); // Ort
			$arrRowData[] = ""; // Reserviert (FIX)
			$arrRowData[] = ""; // Reserviert (FIX)
			$arrRowData[] = strtoupper($dbResult->country); // Land
			$arrRowData[] = $dbResult->phone; // Telefon
			$arrRowData[] = ""; // Telefax
			$arrRowData[] = utf8_decode($dbResult->email); // E-Mail
			$arrRowData[] = "(NULL)"; // KontoNr (BANK)
			$arrRowData[] = "(NULL)"; // BLZ (BANK)
			$arrRowData[] = "(NULL)"; // IBAN (BANK)
			$arrRowData[] = "(NULL)"; // BIC (BANK)
			$arrRowData[] = "(NULL)"; // Bankname (BANK)
			$arrRowData[] = utf8_decode($dbResult->xt_club_membernumber.",".$dbResult->lastname.",".$dbResult->firstname); // Sepa-Mandatsreferenz
			$arrRowData[] = "(NULL)"; // Datum Unterschrift
			$arrRowData[] = date("Ymd", $dbResult->dateOfBirth); // Geburtsdatum
			$arrRowData[] = date("Ymd", $dbResult->dateAdded); // Eintrittsdatum
			$arrRowData[] = ($dbResult->stop != "" ? date("Ymd", $dbResult->stop) : ""); // Austrittsdatum
			$arrRowData[] = ""; // Bemerkung
			$arrRowData[] = $dbResult->mobile; // Telefon2
			$arrRowData[] = ""; // Skype/VoIP
			$arrRowData[] = "(NULL)"; // Inhaber (BANK)
			$arrRowData[] = "1 E-Mail"; // Ausgabemedium (FIX)
			$arrRowData[] = $this->getGroups(deserialize($dbResult->groups, true)); // Adressgruppe
			$arrRowData[] = "(NULL)"; // Zahlungsbed. (BANK)
			$arrRowData[] = "(NULL)"; // Abrechnung über (BANK)
			$arrRowData[] = "0 Deutsch"; // Ausgabesprache
			$arrRowData[] = "(NULL)"; // Kostenstelle (BANK)
			
			$objFile->append(implode(";", $arrRowData));
		}
		
		$objFile->close();

		return $objFile->value;
	}
	
	private function getMemberNumber($nr)
	{
		$strNr = "10";
		
		if ($nr < 10)
		{
			$strNr .= "00";
		}
		else if ($nr < 100)
		{
			$strNr .= "0";
		}
		$strNr .= $nr;
		
		return $strNr;
	}
	
	private function getTitel($gender)
	{
		if ($gender == 'male')
		{
			return "Herr";
		}
		else if ($gender == 'female')
		{
			return "Frau";
		}
		return "";
	}
	
	private function getGroups($arrContaoGroups)
	{
		$arrGroups = array();
		$arrContaoCollmexMapping = array
		(
			'2' => array
			(
				'name' => 'Abteilung - Radsport',
				'collmex_id' => '1'
			),
			'4' => array
			(
				'name' => 'Abteilung - Triathlon',
				'collmex_id' => '2'
			),
			'11' => array
			(
				'name' => 'RSC - Jugend',
				'collmex_id' => '3'
			)
		);
		
		foreach (array_values($arrContaoGroups) as $group)
		{
			if (is_array($arrContaoCollmexMapping[$group]))
			{
				$arrGroups[] = $arrContaoCollmexMapping[$group]['collmex_id'];
			}
		}
		
		$strGroups = implode(",", $arrGroups);
		
		if (empty($strGroups))
		{
			$strGroups = "0";
		}
		return $strGroups;
	}
}

?>