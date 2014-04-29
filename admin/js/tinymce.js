(function() {
	tinymce.PluginManager.add('jm_ltsc_mce_button', function( editor, url ) {
		editor.addButton( 'jm_ltsc_mce_button', {
			icon: false,
			type: 'button',
			text: 'JMLTSC',
			onclick: function() {
				editor.windowManager.open( {
					title: 'Insert Twitter Shortcode',
					body: [	
						{
							type: 'textbox',
							name: 'userValue',
							label: 'Twitter account (without @)',
							value: 'twitterapi'
						},
						{	
							type: 'listbox',
							name: 'countValue',
							label: 'Count',
							'values': [
								{text: '1', value: '1'},
								{text: '2', value: '2'},
								{text: '3', value: '3'},
								{text: '4', value: '4'},
								{text: '5', value: '5'},
								{text: '6', value: '6'},
								{text: '7', value: '7'},
								{text: '8', value: '8'},
								{text: '9', value: '9'},
								{text: '10', value: '10'},	
								{text: '11', value: '11'},
								{text: '12', value: '12'},
								{text: '13', value: '13'},
								{text: '14', value: '14'},
								{text: '15', value: '15'},
								{text: '16', value: '16'},
								{text: '17', value: '17'},
								{text: '18', value: '18'},
								{text: '19', value: '19'},
								{text: '20', value: '20'},								
								]
						},
						{	
							type: 'listbox',
							name: 'cacheValue',
							label: 'Count',
							'values': [
								{text: '30min', value: '1800'},
								{text: '1h', value: '3600'},
								{text: '3h', value: '10800'},
								{text: '4h', value: '14400'},
								{text: '5h', value: '18000 '},
								{text: '6h', value: '21600'},
								{text: '7h', value: '25200'},
								{text: '8h', value: '28800'},
								{text: '9h', value: '32400'},
								{text: '10h', value: '36000'},							
								]
						},
						{
							type: 'textbox',
							name: 'incValue',
							label: 'Include RTs?',
							value: 'false'
						},
						{
							type: 'textbox',
							name: 'excValue',
							label: 'Exclude replies?',
							value: 'true'
						}

					],
					onsubmit: function( e ) {
						editor.insertContent( '[jmlt username="' + e.data.userValue + '" count="' + e.data.countValue + '" cache="' + e.data.cacheValue + '" include_rts="' + e.data.incValue + '" exclude_replies="' + e.data.excValue + '"]');
					}
				});
			}
		});
	});
})();