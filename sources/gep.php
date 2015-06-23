<?PHP


/**
 * Objets de base
 * @package GEP
 * @author Olivier ROUET
 * @version 1.0.0
 */


/**
 * classe GEP_Controleur
 *
 */
class GEP_Controleur
{

	/**
	 * Objet MySQLi
	 *
	 * @access public
	 * @var mixed
	 */
	public $connexion;
	
	
	/**
	 * Adresse du SGBD
	 *
	 * @access public
	 * @var string
	 */
	public $sgbd_serveur;
	
	
	/**
	 * Identifiant de connexion au SGBD
	 *
	 * @access public
	 * @var string
	 */
	public $sgbd_identifiant;
	
	
	/**
	 * Mot de passe de connexion au SGBD
	 *
	 * @access public
	 * @var string
	 */
	public $sgbd_motdepasse;
	
	
	/**
	 * Base de données à utiliser dans le SGBD
	 *
	 * @access public
	 * @var string
	 */
	public $sgbd_base;
	
	
	/**
	 * Constructeur
	 *
	 * @param string $serveur
	 * @param string $identifiant
	 * @param string $motdepasse
	 * @param string $base
	 */
	public function __construct($serveur, $identifiant, $motdepasse, $base)
	{
	
		// intialisation des variables
		$this->connexion = false;
		$this->sgbd_serveur = $serveur;
		$this->sgbd_identifiant = $identifiant;
		$this->sgbd_motdepasse = $motdepasse;
		$this->sgbd_base = $base;
		
		$this->connecter();
	
	}
	
	
	/**
	 * Connection au SGBD
	 *
	 * @return boolean
	 */
	public function connecter()
	{
	
		// initialisation des variables
		$sortie = false;
		
		// traitement
		$connexion = new mysqli(
			$this->sgbd_serveur,
			$this->sgbd_identifiant,
			$this->sgbd_motdepasse,
			$this->sgbd_base
		);
		
		if ($connexion->connect_error) {
		
			die('Connect Error (' . $connexion->connect_errno . ') ' . $connexion->connect_error);
		
		} else {
		
			$this->connexion = $connexion;
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	//
	public function albumAjouter($nom)
	{
	
		// intialisation des variables
		$sortie = false;
		
		// traitement
		$requete = "
			INSERT INTO `gep__albums` (
				`id`,
				`ts`,
				`nom`
			) VALUE (
				null,
				null,
				'" . addslashes($nom) . "'
			);
		";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$insert_id = $this->connexion->insert_id;
			
			$album_gep = $this->albumLire($insert_id);
			
			// Création du compte
			$compte_ged = $this->compteAjouter($album_gep);
			
			if ($compte_ged !== false) {
			
				$sortie = $album_gep;
			
			}
		
		} else {
		
			die($requete);
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	//
	public function albumChercher($nom)
	{
	
		// intialisation des variables
		$sortie = false;
		
		// traitement
		$requete = "SELECT * FROM `gep__albums` WHERE nom = '" . addslashes($nom) . "';";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$nombre = $resultat->num_rows;
			
			if ($nombre === 1) {
			
				$sortie = $resultat->fetch_assoc();
			
			}
		
		} else {
		
			die($requete);
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	//
	public function albumLire($id)
	{
	
		// intialisation des variables
		$sortie = false;
		
		// traitement
		$requete = "SELECT * FROM `gep__albums` WHERE id = " . ($id) . ";";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat) {
		
			$sortie = $resultat->fetch_assoc();
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	//
	public function albumLister($id, $pagination)
	{
	
		// initialisation des variables
		$sortie = false;
		$pagination = false;
		
		// traitement
		$requete = "
			SELECT
				p.id AS id,
				d.lots_id AS lots_id,
				d.nom AS nom,
				d.empreinte AS empreinte
			FROM
				`ged__documents` d,
				`gep__photos` p
			WHERE
				p.albums_id = " . $id . "
				AND d.id = p.documents_id
			ORDER BY
				nom ASC
		";
		// print($requete);
		
		if ($pagination === true) {
		
			$requete_total = "SELECT COUNT(t.id) AS TOTAL FROM (" . $requete . ") t;";
			// print($requete_total);
			
			$resultat = $this->connexion->query($requete_total);
			
			if ($resultat !== false) {
			
				$ligne = mysqli_fetch_assoc($resultat);
				$total = $ligne['TOTAL'];
				
				$pages = ceil($total / $page_pas);
				
				$pages_precedente = $page_debut - $page_pas;
				
				if ($pages_precedente < 0) {
				
					$pages_precedente = 0;
				
				}
				
				$pages_suivante = $page_debut + $page_pas;
				
				if ($pages_suivante >= $total) {
				
					if ($total > $page_pas) {
					
						$pages_suivante = $total - $page_pas;
					
					} else {
					
						$pages_suivante = 0;
					
					}
				
				}
				
				$sql_limit = "LIMIT " . $page_debut . ", " . $page_pas;
				
				$requete_page = $requete . " " . $sql_limit . ";";
				// print($requete_page);
				
				$resultat = $this->connexion->query($requete_page);
			
			}
		
		} else {
		
			$resultat = $this->connexion->query($requete);
		
		}
		
		if ($resultat !== false) {
		
			$sortie = [];
			
			while ($ligne = $resultat->fetch_assoc()) {
			
				$cle = $ligne['id'];
				
				$cible = CHEMIN_STOCKAGE . $ligne['lots_id'] . '/' . $ligne['empreinte'] . '.jpg';
				
				$dimensions = getimagesize($cible);
				$morceaux = explode('.', $ligne['nom']);
				
				$ligne['libelle'] = $morceaux[0];
				$ligne['largeur'] = $dimensions[0];
				$ligne['hauteur'] = $dimensions[1];
				
				$ligne['qualite'] = round((($dimensions[0] * $dimensions[1]) / 1000000), 1);
				
				$sortie[$cle] = $ligne;
			
			}
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	//
	public function albumsLister()
	{
	
		// intialisation des variables
		$sortie = false;
		
		// traitement
		$requete = "
			SELECT
				a.*,
				(SELECT count(p.id) as photos FROM`gep__photos` p WHERE p.albums_id = a.id) AS photos
			FROM
				`gep__albums` a
			;
		";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$sortie = array();
			
			while($ligne = $resultat->fetch_assoc()) {
			
				$sortie[] = $ligne;
			
			}
		
		} else {
		
			die($requete);
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	//
	public function compteAjouter($album)
	{
	
		// intialisation des variables
		$sortie = false;
		$longueur = 15;
		$majuscules = 2;
		$minuscules = 2;
		$chiffres = 2;
		$speciaux = 2;
		$melanges = 3;
		
		// traitement
		$album_id = $album['id'];
		$identifiant = (string) uuid_v4_generer();
		$motdepasse = (string) mdp_generer($longueur, $majuscules, $minuscules, $chiffres, $speciaux, $melanges);
		
		$requete = "
			INSERT INTO `gep__comptes` (
				`id`,
				`ts`,
				`albums_id`,
				`identifiant`,
				`motdepasse`
			) VALUE (
				null,
				null,
				" . ($album_id) . ",
				'" . addslashes($identifiant) . "',
				'" . addslashes($motdepasse) . "'
			);
		";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$insert_id = $this->connexion->insert_id;
			
			$compte_gep = $this->compteLire($insert_id);
			
			$sortie = $compte_gep;
		
		} else {
		
			die($requete);
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	//
	public function compteLire($id)
	{
	
		// intialisation des variables
		$sortie = false;
		
		// traitement
		$requete = "SELECT * FROM `gep__comptes` WHERE id = " . ($id) . ";";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$sortie = $resultat->fetch_assoc();
		
		} else {
		
			die($requete);
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	//
	public function comptesLister()
	{
	
		// intialisation des variables
		$sortie = false;
		
		// traitement
		$requete = "
			SELECT
				a.nom AS album_nom,
				c.id,
				c.identifiant,
				c.motdepasse
			FROM
				`gep__albums` a,
				`gep__comptes` c
			WHERE
				a.id = c.albums_id
			;
		";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$sortie = array();
			
			while ($ligne = $resultat->fetch_assoc()) {
			
				$sortie[] = $ligne;
			
			}
		
		} else {
		
			die($requete);
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	//
	public function photoAjouter($album, $document)
	{
	
		// intialisation des variables
		$sortie = false;
		
		// traitement
		$requete = "
			SELECT
				*
			FROM
				`gep__photos`
			WHERE
				albums_id = " . ($album['id']) . "
				AND documents_id = " . ($document['id']) . "
			;
		";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$nombre = $resultat->num_rows;
			
			if ($nombre == 0) {
			
				// traitement
				$requete2 = "
					INSERT INTO `gep__photos` (
						`id`,
						`ts`,
						`albums_id`,
						`documents_id`
					) VALUE (
						null,
						null,
						'" . ($album['id']) . "',
						'" . ($document['id']) . "'
					);
				";
				
				$resultat2 = $this->connexion->query($requete2);
				
				if ($resultat2 !== false) {
				
					$insert_id = $this->connexion->insert_id;
					
					$sortie = $this->photoLire($insert_id);
				
				} else {
				
					die($requete2);
				
				}
			
			} else {
			
				$sortie = $this->photoLire($document['id']);
			
			}
		
		} else {
		
			die($requete);
		
		}
		
		
		// sortie
		return $sortie;
	
	}
	
	
	//
	public function photoLire($id)
	{
	
		// intialisation des variables
		$sortie = false;
		
		// traitement
		$requete = "SELECT * FROM `gep__photos` WHERE id = " . ($id) . ";";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$sortie = $resultat->fetch_assoc();
		
		} else {
		
			die($requete);
		
		}
		
		// sortie
		return $sortie;
	
	}


}


/**
 * classe GEP_Image
 *
 */
class GEP_Image
{

	//
	public $document;
	
	
	//
	public $hauteur;
	
	
	//
	public $largeur;
	
	
	//
	public function __construct ($document)
	{
	
		// intialisation des variables
		$this->largeur = 0;
		$this->hauteur = 0;
		
		// traitement
		if ($this->documentAssocier($document)) {
		
			$this->analyser();
		
		}
	
	}
	
	
	//
	public function analyser()
	{
	
		// initialisation des variables
		$sortie = false;
		
		// traitement
		$dimensions = getimagesize($this->document);
		
		if ($dimensions !== false) {
		
			$this->largeur = (integer) $dimensions[0];
			$this->hauteur = (integer) $dimensions[1];
			$sortie = true;
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	//
	public function documentAssocier($document)
	{
	
		// intialisation des variables
		$sortie = false;
		$this->document = '';
		
		// On regarde si le document existe
		if (file_exists($document)) {
		
			$this->document = (string) $document;
			$sortie = true;
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	//
	function redimensionner($parametres)
	{
	
		$sortie = false;
		
		$cible = '';
		$largeur = 0;
		$hauteur = 0;
		$type = 'JPEG';
		$r = 0;
		$g = 0;
		$b = 0;
		
		// Lecture des paramètres
		if (isset($parametres['cible'])) {
		
			$cible = (string) $parametres['cible'];
		
		}
		
		if (isset($parametres['largeur'])) {
		
			$largeur = (integer) $parametres['largeur'];
		
		}
		
		if (isset($parametres['hauteur'])) {
		
			$hauteur = (integer) $parametres['hauteur'];
		
		}
		
		if (isset($parametres['type'])) {
		
			$type = (string) $parametres['type'];
		
		}
		
		if (isset($parametres['r'])) {
		
			$r = (integer) $parametres['r'];
		
		}
		
		if (isset($parametres['g'])) {
		
			$g = (integer) $parametres['g'];
		
		}
		
		if (isset($parametres['b'])) {
		
			$b = (integer) $parametres['b'];
		
		}
		
		// Dimensions de la source
		$src_l = $this->largeur;
		$src_h = $this->hauteur;
		
		// Type de destination
		$dst_type = $type;
		
		// Dimensions de la destination
		$dst_l = $largeur;
		$dst_h = $hauteur;
		
		
		// Calcul des variables de l'image de destination
		$dst_x = 0;
		$dst_y = 0;
		
		$src_ratio = $src_l / $src_h;
		$dst_ratio = $dst_l / $dst_h;
		
		if ($dst_ratio <= $src_ratio) {
		
			$dim_l = $dst_l;
			$dim_h = ceil($dim_l / $src_ratio);
			
			$vide_y = $dst_h - $dim_h;
			$dst_y = floor($vide_y / 2);
		
		} else {
		
			$dim_h = $dst_h;
			$dim_l = ceil($dim_h * $src_ratio);
			
			$vide_x = $dst_l - $dim_l;
			$dst_x = floor($vide_x / 2);
		
		}
		
		
		// Lecture de la source
		$src_img = imagecreatefromjpeg($this->document);
		
		if ($src_img !== false) {
		
			// Création de la destination
			$dst_img = imagecreatetruecolor($largeur, $hauteur);
			
			if ($dst_img !== false) {
			
				$fond_couleur = imagecolorallocate($dst_img, $r, $g, $b);
				
				if ($dst_type == 'PNG') {
				
					imagesavealpha($dst_img, true);
					
					$fond_couleur = imagecolorallocatealpha($dst_img, 255, 255, 255, 127);
				
				}
				
				imagefill($dst_img, 0, 0, $fond_couleur);
				
				// Coordonnées de la zone à copier
				$src_x = 0;
				$src_y = 0;
				
				// Resize
				imagecopyresized(
					$dst_img, $src_img,
					$dst_x, $dst_y,
					$src_x, $src_y,
					$dim_l, $dim_h,
					$src_l, $src_h
				);
				
				// Output
				switch ($dst_type) {
				
					case 'JPEG':
					
						$sortie = imagejpeg($dst_img, $cible, 75);
					
					break;
					
					
					case 'PNG':
					
						$sortie = imagepng($dst_img, $cible, 6);
					
					break;
				
				}
				
				// Free memory
				imagedestroy($dst_img);
			
			}
		
		}
		
		// sortie
		return $sortie;
	
	
	}


}


?>