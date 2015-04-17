<?PHP


//
function imagesMiniatureGenerer($image, $miniature, $largeur, $hauteur)
{

	$sortie = false;
	
	$dst_type = 'PNG';
	$dst_type = 'JPEG';
	
	if (file_exists($image)) {
	
		// Dimensions de l'image de destination
		$dst_l = $largeur;
		$dst_h = $hauteur;
		
		
		
		// Source
		$src_dimensions = getimagesize($image);
		$src_l = $src_dimensions[0];
		$src_h = $src_dimensions[1];
		
		
		
		// Création de l'image redimensionnée
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
		
		
		
		//
		$src_img = imagecreatefromjpeg($image);
		
		$dst_img = imagecreatetruecolor($largeur, $hauteur);
		
		// $fond_couleur = imagecolorallocate($dst_img, 0, 0, 0);
		$fond_couleur = imagecolorallocate($dst_img, 39, 43, 49);
		
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
			
				$sortie = imagejpeg($dst_img, $miniature, 75);
			
			break;
			
			
			case 'PNG':
			
				$sortie = imagepng($dst_img, $miniature, 6);
			
			break;
		
		}
		
		
		
		// Free memory
		imagedestroy($dst_img);
	
	}
	
	return $sortie;


}


//
class GEP_Controleur
{

	//
	public $connexion;
	
	
	//
	public function __construct ($serveur, $identifiant, $motdepasse, $base)
	{
	
		// intialisation des variables
		$this->connexion = false;
		
		// SQL
		$connexion = mysqli_connect(
			$serveur,
			$identifiant,
			$motdepasse,
			$base
		);
		
		if ($connexion !== false) {
		
			$this->connexion = $connexion;
		
		} else {
		
			die("Problème de connexion au serveur SQL");
		
		}
	
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
		
		$resultat = mysqli_query($this->connexion, $requete);
		
		if ($resultat !== false) {
		
			$insert_id = mysqli_insert_id($this->connexion);
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
		
		$resultat = mysqli_query($this->connexion, $requete);
		
		if ($resultat !== false) {
		
			$nombre = mysqli_num_rows($resultat);
			
			if ($nombre === 1) {
			
				$sortie = mysqli_fetch_assoc($resultat);
			
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
		
		$resultat = mysqli_query($this->connexion, $requete);
		
		if ($resultat !== false) {
		
			$sortie = mysqli_fetch_assoc($resultat);
		
		} else {
		
			die($requete);
		
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
		// traitement
		$requete = "
			SELECT
				a.*,
				(SELECT count(p.id) as photos FROM`gep__photos` p WHERE p.albums_id = a.id) AS photos
			FROM
				`gep__albums` a
			;
		";
		
		$resultat = mysqli_query($this->connexion, $requete);
		
		if ($resultat !== false) {
		
			$sortie = array();
			
			while($ligne = mysqli_fetch_assoc($resultat)) {
			
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
		
		$resultat = mysqli_query($this->connexion, $requete);
		
		if ($resultat !== false) {
		
			$insert_id = mysqli_insert_id($this->connexion);
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
		
		$resultat = mysqli_query($this->connexion, $requete);
		
		if ($resultat !== false) {
		
			$sortie = mysqli_fetch_assoc($resultat);
		
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
		
		$resultat = mysqli_query($this->connexion, $requete);
		
		if ($resultat !== false) {
		
			$sortie = array();
			
			while ($ligne = mysqli_fetch_assoc($resultat)) {
			
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
		
		$resultat = mysqli_query($this->connexion, $requete);
		
		if ($resultat !== false) {
		
			$nombre = mysqli_num_rows($resultat);
			
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
				
				$resultat2 = mysqli_query($this->connexion, $requete2);
				
				if ($resultat2 !== false) {
				
					$insert_id = mysqli_insert_id($this->connexion);
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
		
		$resultat = mysqli_query($this->connexion, $requete);
		
		if ($resultat !== false) {
		
			$sortie = mysqli_fetch_assoc($resultat);
		
		} else {
		
			die($requete);
		
		}
		
		// sortie
		return $sortie;
	
	}


}


?>