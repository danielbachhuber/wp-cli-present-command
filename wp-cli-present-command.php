<?php
class WP_CLI_Present_Command extends WP_CLI_Command {

	/**
	 * Present your story using WP-CLI.
	 * 
	 * @synopsis <file>
	 */
	public function __invoke( $args ) {

		list( $file ) = $args;

		if ( ! file_exists( $file ) )
			WP_CLI::error( "File to present doesn't exist." );

		$presentation = file_get_contents( $file );

		// Slides are denoted by h1 or h2
		preg_match_all( '/[#]{1,2}.*\r?\n([^#]|\r?\n)*/', $presentation, $slides );
		$slides = $slides[0];

		$i = 0;
		while( $i < count( $slides ) ) {

			if ( $i < 0 )
				exit;

			WP_CLI::line( $slides[$i] );

			$ret = $this->prompt( "Action (j/k)" );
			if ( 'j' == $ret ) {
				$i++;
			} else if ( 'k' == $ret ) {
				$i--;
			}
		}
	}

	/**
	 * Prompt the user for some input.
	 */
	private function prompt( $text ) {
		WP_CLI::out( $text . ':' );
		
		return trim( fgets( STDIN ) );
	}

}
WP_CLI::add_command( 'present', 'WP_CLI_Present_Command' );