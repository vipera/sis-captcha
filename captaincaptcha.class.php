<?php

class CaptainCaptcha
{
	// directorij gdje su fontovi pohranjeni
	protected $fontdir = 'fonts/';

	protected $captcha_size = array(250, 80);

	protected $captcha_letters = 6;

	protected $random_colors = array(
		// crvenkasta i plava
		array(200, 100, 90),
		array(58, 112, 202),

		// žućkasta i siva
		array(198, 219, 102),
		array(94, 97, 80),

		// roza
		array(199, 147, 218),
		array(251, 216, 96),
	);

	// ---------------------------------------------------------------------------------------------
	protected $session_id;
	protected $image = null;
	protected $versions = array();

	// version constants
	public static $VERSION_NORMAL		= 1;
	public static $VERSION_TEXTURE		= 2;
	public static $VERSION_DOUBLE_TEXT	= 3;
	public static $VERSION_SLEEP		= 4;
	public static $VERSION_PLAIN		= 5;
	public static $VERSION_NOROTATION	= 6;
	public static $VERSION_COLORFUL		= 7;
	public static $VERSION_EASY			= 8;

	public function CaptainCaptcha($session_id, $versioning_information = array())
	{
		$this->session_id = $session_id;
		$this->versions = $versioning_information;

		// randomiziraj boje malo
		foreach ($this->random_colors as &$color)
		{
			foreach ($color as &$component)
			{
				$component += mt_rand(-50, 50);
			}
		}

		// TODO: check if session started
		
	}

	public function __destruct__()
	{
		if ($this->image != null)
		{
			// oslobodi resurse
			imagedestroy($this->image);
			$this->image = null;
		}
	}
	
	/**
	 * Kreiraj nasumičan znakovni niz duljine $length koji izbjegava slična slova radi prikaza u CAPTCHI.
	 */
	protected function make_random_text($length = 6, $lowercase_only = false)
	{
		$chars = array(
			// izbjegavati slova i brojeve koji nalikuju radi sprečavanja krivih upisa
			'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'k', 'm', 'n', 'p', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
			'A', 'B', 'D', 'E', 'F', 'G', 'H', 'M', 'N', 'R', 'T', 'Y',
			'1', '2', '3', '4', '5', '6', '7', '8', '9',
		);

		if ($lowercase_only)
		{
			$chars = array_filter($chars, create_function('$c','return preg_match("#[a-z]#", $c);'));
		}

		$text = "";
		for ($i = $length; $i > 0; $i--)
		{
			$text .= $chars[mt_rand(0, count($chars) - 1)];
		}
		return $text;
	}

	/**
	 * Dohvati nasumičan par boja.
	 */
	protected function get_random_color_pair_for_image(&$image)
	{
		$colors = array();

		$color_set_no = mt_rand(0, count($this->random_colors) - 1);
		list($red, $green, $blue) = $this->random_colors[$color_set_no];
		$colors[] = imagecolorallocate($image, $red, $green, $blue);

		$index_offset = 1;
		if (($color_set_no % 2) == 1)
			$index_offset = -1;

		list($red, $green, $blue) = $this->random_colors[$color_set_no + $index_offset];
		$colors[] = imagecolorallocate($image, $red, $green, $blue);

		return $colors;
	}

	/**
	 * Pronađi dostupne fontove iz $fontdir-a.
	 */
	protected function get_available_fonts()
	{
		$found_font_files = array();

		if ($handle = opendir($this->fontdir))
		{
			while (false !== ($entry = readdir($handle)))
			{
				if ($entry != '.' && $entry != '..'
					&& preg_match('#.*\\.ttf$#', $entry)
					&& is_readable($this->fontdir . $entry)
				)
				{
					$found_font_files[] = $entry;
				}
			}
		}
		closedir($handle);

		return $found_font_files;
	}

	/**
	 * Dohvati nasumičan naziv TTF-a iz lokalog font direktorija.
	 */
	protected function get_random_font()
	{
		$fonts = $this->get_available_fonts();
		if (count($fonts > 0))
			return $fonts[mt_rand(0, count($fonts) - 1)];
		return null;
	}

	/**
	 * Generiraj CAPTCHU.
	 */
	function &make_captcha_image()
	{
		if ($this->image != null)
		{
			imagedestroy($this->image);
			$this->image = null;
		}

		// kreirati sliku
		list($w, $h) = $this->captcha_size;
		$image = imagecreatetruecolor($w, $h);

		// postaviti boje koje će se koristiti
		$black = imagecolorallocate($image, 0, 0, 0);
		$white = imagecolorallocate($image, 255, 255, 255);
		$purepink = imagecolorallocate($image, 255, 0, 255); // transparentna
		imagecolortransparent($image, $purepink);

		// dohvati nasumičan par (vjerojatno donekle kontrasnih) boja za slova i pozadinu
		$colors = $this->get_random_color_pair_for_image($image);

		imagefilledrectangle($image, 0, 0, $w - 1, $h - 1, $colors[0]);

		// kreiraj pozadinsku teksturu (VERZIJA 2)
		if ($this->if_version(self::$VERSION_TEXTURE))
		{
			$startpixels = array(0, 2, 4);

			for ($i = 0; $i < $w; $i += 5)
			{
				for ($j = 0; $j < $h; $j += 5)
				{
					$pos = mt_rand(0, 5);

					if ($pos > count($startpixels) - 1)
						continue;

					$startpixel = $startpixels[$pos];
					$endpixel = 4 - $startpixel;

					//imageline($image, $i + $startpixel, $i, $j + $endpixel, $j + 4, $colors[1]);
					imageline($image, $i + $startpixel, $j, $i + $endpixel + 1, $j + 5, $colors[1]);
				}
			}
		}

		// generiraj text
		$text = $this->make_random_text($this->captcha_letters, $this->if_version(self::$VERSION_EASY));
		$_SESSION[$this->session_id] = md5($text);

		if ($this->if_version(self::$VERSION_EASY))
		{
			imagettftext(
				$image,
				20,
				0,
				30,
				40,
				$black,
				$this->fontdir . 'verdana.ttf',
				$text
			);
		}
		else
		{
			// pisati svako slovo po slici posebno rotirano
			$x_offset = mt_rand(1, 5);

			foreach (str_split($text) as $char)
			{
				$sub_size = 42; // floor($w / $captcha_letters);
								// nažalost vrlo ne-nasumično
				$fontsize = mt_rand(18, 35);
				$angle = $this->if_version(self::$VERSION_NOROTATION) ? 0 : mt_rand(-20, 20);

				// kreirati sliku ovog slovo
				$letterimg = imagecreatetruecolor($sub_size, $sub_size);

				// očuvaj prozirnost radi prijenosa na veću sliku
				imagealphablending($letterimg, false);
				imagesavealpha($letterimg, true);

				imagefilledrectangle($letterimg, 0, 0, $sub_size - 1, $sub_size - 1, $purepink);
				imagecolortransparent($letterimg, $purepink);

				$current_color_set = array();
				if ($this->if_version(self::$VERSION_COLORFUL))
				{
					// dodaj sve boje u polje dostupnih boja radi druge ture nasumičnih oblika
					foreach ($this->random_colors as $color)
					{
						list($red, $green, $blue) = $color;
						$current_color_set[] = imagecolorallocate($image, $red, $green, $blue);
					}
				}
				else
				{
					$current_color_set = $colors;
				}

				imagettftext(
					$letterimg,
					$fontsize,
					$angle,
					floor($sub_size / 2) - $fontsize / 2,
					floor($sub_size / 2) + $fontsize / 2,
					$this->if_version(self::$VERSION_COLORFUL) ? $current_color_set[mt_rand(0, count($current_color_set) - 1)] : $current_color_set[1],
					$this->fontdir . $this->get_random_font(),
					$char
				);
				
				// shearing - dodano u PHP 5.5.0, trenutno rijetko gdje zastupljeno, nažalost
				if (version_compare(phpversion(), '5.5.0', '>'))
				{
					// shear paralelan s x-osi
					$letterimg = imageaffine(
						$letterimg,
						array
						( 1, 10,
						  0, 1,
						  0, 0 ),
						array('x' => 0, 'y' => 0, 'width' => $sub_size, 'height' => $sub_size)
					);
				}

				$placedwidth = mt_rand($sub_size - 15, $sub_size + 5);
				imagecopyresized(
					$image,
					$letterimg,
					$x_offset,
					mt_rand(0, $placedwidth - 10),
					0,
					0,
					$sub_size + mt_rand(-5, 5),//$placedwidth,
					$sub_size + mt_rand(-5, 5),//$placedwidth + mt_rand(-5, 5),
					$sub_size,
					$sub_size
				);

				if ($this->if_version(self::$VERSION_DOUBLE_TEXT))
				{
					$tinyoffset = mt_rand(10, 18);

					imagecopyresized(
						$image,
						$letterimg,
						$x_offset + $tinyoffset,
						mt_rand(0, $placedwidth - 10) + $tinyoffset,
						0,
						0,
						$sub_size - $tinyoffset,
						$sub_size - $tinyoffset,
						$sub_size,
						$sub_size
					);
				}
				
				$x_offset += $placedwidth + mt_rand(-5, 5);

				imagedestroy($letterimg);
			}

			if (!$this->if_version(self::$VERSION_PLAIN))
			{
				// dodaj malo linija preko slike radi teže čitljivosti
				for ($i = 0; $i < 3; $i++)
				{
					imageline($image, mt_rand(0, $w), mt_rand(0, $h), mt_rand(0, $w), mt_rand(0, $h), $colors[1]);

					imageellipse(
						$image,
						mt_rand(20, $w - 20),
						mt_rand(20, $w - 20),
						mt_rand($w / 2 - 30 * ($i + 1), $w / 2 + 30 * ($i + 1)),
						mt_rand($w / 2 - 30 * ($i + 1), $w / 2 + 30 * ($i + 1)),
						$colors[1]
					);

					$rx1 = mt_rand(20, $w - 20);
					$ry1 = mt_rand(20, $w - 20);

					$rx2 = $rx1 + mt_rand(1, 20);
					$ry2 = $ry1 + mt_rand(1, 20);
					imageline($image, $rx1, $ry1, $rx2, $ry2, $colors[1]);
					imageline($image, $rx1 + 1, $ry1 + 1, $rx2 + 1, $ry2 + 1, $colors[1]);
				}

				// dodaj sve boje u polje dostupnih boja radi druge ture nasumičnih oblika
				foreach ($this->random_colors as $color)
				{
					list($red, $green, $blue) = $color;
					$colors[] = imagecolorallocate($image, $red, $green, $blue);
				}

				// kreiraj nekoliko dodatnih nasumičnih elemenata preko teksta
				for ($i = 0; $i < 2; $i++)
				{
					imageline($image, mt_rand(0, $w), mt_rand(0, $h), mt_rand(0, $w), mt_rand(0, $h), $colors[mt_rand(0, count($colors) - 1)]);

					imageellipse(
						$image,
						mt_rand(10, $w - 10),
						mt_rand(10, $w - 10),
						mt_rand($w / 2 - 30 * ($i + 1), $w / 2 + 30 * ($i + 1)),
						mt_rand($w / 2 - 30 * ($i + 1), $w / 2 + 30 * ($i + 1)),
						$colors[mt_rand(0, count($colors) - 1)]
					);
				}
			}
		}

		return $image;
	}

	protected function if_version($version)
	{
		if (in_array($version, $this->versions))
			return true;
		return false;
	}

	public function render()
	{
		$img = &$this->make_captcha_image();

		// opcionalno spavaj sekundu radi otežavanja napada
		if ($this->if_version(self::$VERSION_SLEEP))
			sleep(1);

		// prikaži sliku
		header("Content-type: image/png");  
		imagepng($img);
	}
}

?>