// eslint-disable-next-line no-jquery/no-global-selector
$( '.ext-nuke-dateTimeField[data-ooui!=""]' )
	.each( ( _index, element ) => {
		const field = OO.ui.FieldLayout.static.infuse( $( element ) );
		const input = field.getField();

		const moment = require( 'moment' );

		function validate() {
			input.getValidity()
				.then( () => {
					field.setErrors( [] );
				} )
				.catch( () => {
					moment.relativeTimeRounding( Math.floor );
					field.setErrors( [
						mw.msg(
							'nuke-date-limited',
							// Regenerate moment object using just the date to get a timestamp
							// at 0 UTC of the specified date, instead of local time.
							moment.utc( input.mustBeAfter.format( 'YYYY-MM-DD' ) )
								.utc()
								// `mustBeAfter` is set to always be one day before the `max` in
								// DateInputWidget::__construct. We need to add a day to get the
								// original value back.
								.add( 1, 'day' )
								// Get relative time, without the suffix (e.g. "ago").
								.fromNow( true )
						)
					] );
					moment.relativeTimeRounding();
				} );
		}

		input.on( 'change', validate );
		validate();
	} );
