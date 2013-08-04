<?php
class WP_CLI_Present_Command extends WP_CLI_Command {

	private $height;

	/**
	 * Present your story using WP-CLI.
	 * 
	 * @synopsis <file> [--screen-height=<height>]
	 */
	public function __invoke( $args, $assoc_args ) {

		list( $file ) = $args;

		$defaults = array(
				'screen-height' => 20,
			);
		$assoc_args = array_merge( $defaults, $assoc_args );
		$this->height = $assoc_args['screen-height'];

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

			$ret = $this->prompt( "(#/j/k/q)" );
			switch ( $ret ) {
				case is_numeric( $ret ):
					$i = $ret - 1;
					break;
				case 'j':
					$i++;
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
		// Slides are denoted by h1 or h2
		preg_match_all( '/[#]{1,2}.*\r?\n([^#]|\r?\n)*/', $presentation, $slides );
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
		foreach( $slide_lines as $slide_line ) {
			WP_CLI::line( $slide_line );
			$count++;
		}

		while( $count < $this->height ) {
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
WP_CLI::add_command( 'present', 'WP_CLI_Present_Command' );