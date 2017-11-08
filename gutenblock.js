( function( $, wp, i18n ) {

	wp.blocks.registerBlockType( 'secret-santa/ui', {
		title : i18n['Holiday Gift Exchange'],
		icon : 'screenoptions',
		category : 'common',

		attributes : {
			event : {
				type : 'string',
				default : ''
			},
			state : {
				type : 'integer',
				default : '1'
			}
		},

		edit : function( props ) {
			var stateLabels = {
				'1' : i18n['state1'],
				'2' : i18n['state2'],
				'3' : i18n['state3'],
				'4' : i18n['state4'],
			};
			function handleEventChange( eventArr ) {
				if ( 0 === eventArr.length ) {
					props.setAttributes({
						event : ''
					});
				} else {
					props.setAttributes({
						event : eventArr[ 0 ]
					});
				}
			}

			return wp.element.createElement(
				'div',
				null,
				[
					wp.element.createElement(
						'h3',
						{
							key : 'secret-santa/title'
						},
						i18n['Holiday Gift Exchange']
					),
					wp.element.createElement(
						'section',
						{
							key : 'secret-santa/event'
						},
						[
							i18n['Event:'],
							wp.element.createElement(
								wp.components.FormTokenField,
								{
									key : 'secret-santa/event/form-token-field',
									suggestions : window.secretSantaGutenblock.events,
									value : props.attributes.event ? [ props.attributes.event ] : [],
									maxLength : 1,
									disabled : false,
									onChange : handleEventChange
								}
							),
						]
					),
					wp.element.createElement(
						wp.components.Dropdown,
						{
							key : 'secret-santa/state',
							className : 'secret-santa-state',
							contentClassName : 'secret-santa-state-picker',
							position : 'bottom right',
							renderToggle : function( args ) {
								return wp.element.createElement(
									'button',
									{
										'aria-expanded' : args.isOpen,
										onClick : args.onToggle,
										className : 'button button-secondary'
									},
									i18n['Current State:'] + ' ' + i18n[ 'state' + props.attributes.state ]
								);
							},
							renderContent : function( args ) {
								function handleStateChange( event ) {
									props.setAttributes({
										state : parseInt( event.target.dataset.state, 10 )
									});
									args.onClose();
								}
								var options = [];

								for ( thisState in stateLabels ) {
									options.push(
										wp.element.createElement(
											'li',
											{
												key : 'secret-santa/picker/options/' + thisState,
												className : ( parseInt( thisState, 10 ) === props.attributes.state ) ? 'current' : ''
											},
											wp.element.createElement(
												'a',
												{
													'data-state' : thisState,
													href : '#state:' + thisState,
													onClick : handleStateChange
												},
												i18n[ 'state' + thisState ]
											)
										)
									);
								}

								return wp.element.createElement(
									'ul',
									{},
									options
								);
							}
						}
					)
				]
			);
		},

		save : function() {
			return null;
		}

	} );
} )( jQuery, window.wp, window.secretSantaGutenblock.strings );