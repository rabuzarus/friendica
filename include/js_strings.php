<?php
use Friendica\Core\L10n;

function js_strings() {
	return replace_macros(get_markup_template('js_strings.tpl'), array(
		'$delitem'     => L10n::t('Delete this item?'),
		'$comment'     => L10n::t('Comment'),
		'$showmore'    => L10n::t('show more'),
		'$showfewer'   => L10n::t('show fewer'),
		'$submit'      => L10n::t('Submit'),

		// Translatable prefix and suffix strings for jquery.timeago -
		// using the defaults set below if left untranslated, empty strings if
		// translated to "NONE" and the corresponding language strings
		// if translated to anything else.
		'$t01' => ((L10n::t('timeago.prefixAgo') == 'timeago.prefixAgo') ? '' : ((L10n::t('timeago.prefixAgo') == 'NONE') ? '' : L10n::t('timeago.prefixAgo'))),
		'$t02' => ((L10n::t('timeago.prefixFromNow') == 'timeago.prefixFromNow') ? '' : ((L10n::t('timeago.prefixFromNow') == 'NONE') ? '' : L10n::t('timeago.prefixFromNow'))),
		'$t03' => ((L10n::t('timeago.suffixAgo') == 'timeago.suffixAgo') ? 'ago' : ((L10n::t('timeago.suffixAgo') == 'NONE') ? '' : L10n::t('timeago.suffixAgo'))),
		'$t04' => ((L10n::t('timeago.suffixFromNow') == 'timeago.suffixFromNow') ? 'from now' : ((L10n::t('timeago.suffixFromNow') == 'NONE') ? '' : L10n::t('timeago.suffixFromNow'))),

		// Translatable main strings for jquery.timeago.
		'$t05' => L10n::t('less than a minute'),
		'$t06' => L10n::t('about a minute'),
		'$t07' => L10n::t('%d minutes'),
		'$t08' => L10n::t('about an hour'),
		'$t09' => L10n::t('about %d hours'),
		'$t10' => L10n::t('a day'),
		'$t11' => L10n::t('%d days'),
		'$t12' => L10n::t('about a month'),
		'$t13' => L10n::t('%d months'),
		'$t14' => L10n::t('about a year'),
		'$t15' => L10n::t('%d years'),
		'$t16' => L10n::t(' '), // wordSeparator
		'$t17' => ((L10n::t('timeago.numbers') != 'timeago.numbers') ? L10n::t('timeago.numbers') : '[]'),

		'$January'   => L10n::t('January'),
		'$February'  => L10n::t('February'),
		'$March'     => L10n::t('March'),
		'$April'     => L10n::t('April'),
		'$May'       => L10n::t('May','long'),
		'$June'      => L10n::t('June'),
		'$July'      => L10n::t('July'),
		'$August'    => L10n::t('August'),
		'$September' => L10n::t('September'),
		'$October'   => L10n::t('October'),
		'$November'  => L10n::t('November'),
		'$December'  => L10n::t('December'),

		'$Jan' => L10n::t('Jan'),
		'$Feb' => L10n::t('Feb'),
		'$Mar' => L10n::t('Mar'),
		'$Apr' => L10n::t('Apr'),
		'$MayShort' => L10n::t('May','short'),
		'$Jun' => L10n::t('Jun'),
		'$Jul' => L10n::t('Jul'),
		'$Aug' => L10n::t('Aug'),
		'$Sep' => L10n::t('Sep'),
		'$Oct' => L10n::t('Oct'),
		'$Nov' => L10n::t('Nov'),
		'$Dec' => L10n::t('Dec'),

		'$Sunday'    => L10n::t('Sunday'),
		'$Monday'    => L10n::t('Monday'),
		'$Tuesday'   => L10n::t('Tuesday'),
		'$Wednesday' => L10n::t('Wednesday'),
		'$Thursday'  => L10n::t('Thursday'),
		'$Friday'    => L10n::t('Friday'),
		'$Saturday'  => L10n::t('Saturday'),

		'$Sun' => L10n::t('Sun'),
		'$Mon' => L10n::t('Mon'),
		'$Tue' => L10n::t('Tue'),
		'$Wed' => L10n::t('Wed'),
		'$Thu' => L10n::t('Thu'),
		'$Fri' => L10n::t('Fri'),
		'$Sat' => L10n::t('Sat'),

		'$today'  => L10n::t('today','calendar'),
		'$month'  => L10n::t('month','calendar'),
		'$week'   => L10n::t('week','calendar'),
		'$day'    => L10n::t('day','calendar'),
		'$allday' => L10n::t('All day','calendar')
	));
}
