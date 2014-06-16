<h2>{{$header}}</h2>

<div class="descriptive-text">{{$text}}</div>

<br />

<img src="addon/donate/tipping.jpg" alt="Donations" style="max-width: 95%;"/>

<br />
<br />

<form method="post" action="https://www.paypal.com/cgi-bin/webscr">
<input type="hidden" value="_donations" name="cmd">

<div class="descriptive-text">{{$choice}}</div>
<br />

<select name="business">
{{foreach $contributors as $c}}
<option value="{{$c[1]}}" title="{{$c.2}}" {{if $c[1] === 'max@macgirvin.com'}}selected="selected"{{/if}} >{{$c[0]}}</option>
{{/foreach}}
</select>
<br /><br /><br />
<input type="hidden" value="US" name="lc">
<input type="hidden" value="RedMatrix Donation" name="item_name">
<input type="hidden" value="0" name="no_note">
<input type="hidden" value="USD" name="currency_code">
<input type="submit" name="submit" value="{{$onetime}}" class="btn btn-default" />
</form>

{{if 0}}
{{$repeat}}
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick" />
<input type="hidden" name="hosted_button_id" value="FHV36KE28CYM8" />
<br />
<input type="hidden" name="on0" value="Recurring Donation Options" />Recurring Donation Options<br />
<select name="os0">
	<option value="Option 1">Option 1 : $3.00USD - monthly</option>
	<option value="Option 2">Option 2 : $5.00USD - monthly</option>
	<option value="Option 3">Option 3 : $10.00USD - monthly</option>
	<option value="Option 4">Option 4 : $20.00USD - monthly</option>
</select>
<p>
<input type="hidden" name="currency_code" value="USD" />
<input type="image" name="submit" src="https://www.paypalobjects.com/en_US/i/btn/btn_subscribeCC_LG.gif" alt="PayPal - The safer, easier way to pay online!" /><img src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" alt="" width="1" height="1" border="0" /></p></form>
{{/if}}
