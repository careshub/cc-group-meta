jQuery(document).ready(function() {
	if (jq.cookie('bp-groups-channel')) {
		jq('li#groups-filter-by-channel select').val(jq.cookie('bp-groups-channel'));
	}

	jq('li#groups-filter-by-channel select').change( function() {
		if ( jq('.item-list-tabs li.selected').length )
			var el = jq('.item-list-tabs li.selected');
		else
			var el = jq(this);

		var css_id = el.attr('id').split('-'),
			object = css_id[0],
			scope = css_id[1],
			status = jq(this).val(),
			filter = jq('select#groups-order-by').val(),
			search_terms = '';

		jq.cookie('bp-groups-channel',status,{ path: '/' });

		if ( jq('.dir-search input').length )
			search_terms = jq('.dir-search input').val();

		console.log('search_terms: ' + search_terms);

		bp_filter_request( object, filter, scope, 'div.' + object, search_terms, 1, jq.cookie('bp-' + object + '-extras') );

		return false;
	});
});