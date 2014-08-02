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
		$slide_lines = explode( PHP_EOL, $slide );

		WP_CLI::line();
		WP_CLI::line();

		$header = array_shift( $slide_lines );
		WP_CLI::line( WP_CLI::colorize( '%1' . $header . '%n' ) );

		WP_CLI::line();

		$count = 0;
		$current_colorize = '%n';
		foreach( $slide_lines as $slide_line ) {
			if ( '```' == $slide_line )
				$slide_line = $current_colorize = ( '%n' == $current_colorize ) ? '%g' : '%n';

			if ( 0 === strpos( $slide_line, '%' ) )
				WP_CLI::line( WP_CLI::colorize( $slide_line . ' ' ) );
			else {
				if ( false !== strpos( $slide_line, '`' ) ) {
					$slide_line = preg_replace( '/[\`](.+)[\`]/', '%g$1%n', $slide_line );
					$slide_line = WP_CLI::colorize( $slide_line );
				}
				WP_CLI::line( $slide_line );
			}

			$count++;
		}

		// @todo figure out why height isn't correct
		while( $count < ( $this->height - 5 ) ) {
			WP_CLI::line();
			$count++;
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