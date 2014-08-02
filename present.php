<?php
/**
 * Present your story using WP-CLI
 */
class Present_Command extends WP_CLI_Command {

	private $height;

	/**
	 * Present your story using WP-CLI
	 *
	 * <file>
	 * : Markdown file with your presentation contents.
	 *
	 * @when before_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {

		list( $file ) = $args;

		$this->height = shell_exec( 'tput lines' );
		$this->width = shell_exec( 'tput cols' );

		if ( ! file_exists( $file ) )
			WP_CLI::error( "File to present doesn't exist." );

		$presentation = file_get_contents( $file );

		$slides = $this->get_slides( $presentation );

		$i = 0;
		while( $i < count( $slides ) ) {

			if ( $i < 0 )
				exit;

			$this->display_slide( $slides[$i] );

			$slide_count = $i + 1;
			WP_CLI::out( sprintf( "%d/%d ", $slide_count, count( $slides ) ) );

			$ret = $this->prompt( "(#/<ret>/j/k/q)" );
			switch ( $ret ) {
				case 'k':
					$i--;
					break;
				case '':
				case 'j':
					$i++;
					break;
				case is_numeric( $ret ):
					$i = $ret - 1;
					break;
				case 'k':
					$i--;
					break;
				case 'q';
					exit;
			}
		}
	}

	/**
	 * Get the slides from a given presentation
	 */
	private function get_slides( $presentation ) {
		// Slides are denoted by 3 or more "*" characters
		preg_match_all( '/([^*]{3,})\r?\n/', $presentation, $slides );
		return $slides[0];
	}

	/**
	 * Display a given slide
	 */
	private function display_slide( $slide ) {
		$built_slide_lines = array();

		// Title or subtitle slides are centered horizontally and verically
		if ( preg_match( "#(.+)\r?\n([=-]){1,}\r?\n(.+)?#s", $slide, $matches ) ) {

			// Title slide is red background
			if ( '=' === $matches[2] ) {
				$background_color = '%1';
			// subtitle slides are blue background
			} else if ( '-' === $matches[2] ) {
				$background_color = '%4';
			}

			$built_slide_lines[] = $background_color;

			$center_pieces = array();
			// Header
			$center_pieces[] = '%N' . $background_color . strtoupper( $matches[1] );

			if ( ! empty( $matches[3] ) ) {
				$extra_pieces = explode( PHP_EOL, $matches[3] );
				foreach( $extra_pieces as $extra_piece ) {
					$center_pieces[] = $extra_piece;
				}
			}

			if ( count( $center_pieces ) < $this->height ) {
				$total_diff = $this->height - count( $center_pieces );
				$built_slide_lines = array_pad( $built_slide_lines, floor( $total_diff / 2 ), '' );
			}
			foreach( $center_pieces as $center_piece ) {
				$center_width = cli\safe_strlen( $center_piece );
				$pad = str_repeat( ' ', floor( ( $this->width - $center_width ) / 2 ) );
				$built_slide_lines[] = $background_color . $pad . $center_piece;
			}

			// Pad the rest of the slide
			if ( count( $built_slide_lines ) < ( $this->height - 1 ) ) {
				$built_slide_lines = array_pad( $built_slide_lines, $this->height - 1, $background_color );
			}

			$built_slide_lines[] = '%0';

			$built_slide_str = implode( PHP_EOL, $built_slide_lines );
			$built_slide_str = WP_CLI::colorize( $built_slide_str );
			$built_slide_lines = explode( PHP_EOL, $built_slide_str );

		} else {
			$slide_lines = explode( PHP_EOL, $slide );
			$background_color = '';

			$current_colorize = '%n';
			foreach( $slide_lines as $slide_line ) {
				// Start / end code blocks
				if ( '```' == $slide_line )
					$slide_line = $current_colorize = ( '%n' == $current_colorize ) ? '%g' : '%n';

				if ( 0 === strpos( $slide_line, '%' ) )
					$built_slide_lines[] = WP_CLI::colorize( $slide_line . ' ' );
				else {
					if ( false !== strpos( $slide_line, '`' ) ) {
						$slide_line = preg_replace( '/[\`](.+)[\`]/', '%g$1%n', $slide_line );
						$slide_line = WP_CLI::colorize( $slide_line );
					}
					$built_slide_lines[] = $slide_line;
				}
			}

		}

		if ( count( $built_slide_lines ) < ( $this->height - 1 ) ) {
			$built_slide_lines = array_pad( $built_slide_lines, $this->height - 1, $background_color );
		}

		foreach( $built_slide_lines as $built_slide_line ) {
			WP_CLI::line( $built_slide_line );
		}
	}

	/**
	 * Prompt the user for some input.
	 */
	private function prompt( $text ) {
		WP_CLI::out( $text . ':' );
		return strtolower( trim( fgets( STDIN ) ) );
	}

}
WP_CLI::add_command( 'present', 'Present_Command' );