<html>
<head>
  <title></title>
    {*<link href="../../../backend/css/icons.css" rel="stylesheet" type="text/css" />*}
    <link rel="stylesheet" type="text/css" href="//cdn.shopware.de/assets/library/resources/css/all.css" />
    <link href="{link file='backend/user_price/_resources/css/multiselect.css'}" rel="stylesheet" type="text/css" />
    <link href="{link file='backend/user_price/_resources/css/premium_shipping.css'}" rel="stylesheet" type="text/css" />
    {*<link href="../../../backend/css/icons.css" rel="stylesheet" type="text/css" />*}
    {*<link href="../../../backend/css/icons4.css" rel="stylesheet" type="text/css" />*}
    <script type="text/javascript" src="http://cdn.shopware.de/assets/library/base.js"></script>
    <script type="text/javascript" src="http://cdn.shopware.de/assets/library/all.js"></script>
    <script type="text/javascript" src="{link file='backend/user_price/_resources/js/TabCloseMenu.js'}"></script>
    <script type="text/javascript" src="{link file='backend/user_price/_resources/js/DDView.js'}"></script>
    <script type="text/javascript" src="{link file='backend/user_price/_resources/js/MultiSelect.js'}"></script>
    <script type="text/javascript" src="{link file='backend/user_price/_resources/js/ItemSelector.js'}"></script>
    <script type="text/javascript" src="http://cdn.shopware.de/assets/library/locale/de.js" charset="utf-8"></script>
    <script type="text/javascript" src="{link file='backend/user_price/_resources/js/mootools.js'}"></script>

<script type="text/javascript">
    Ext.onReady(function(){

    	Ext.QuickTips.init();

    	var groupstore = new Ext.data.Store({
    		url: '{url action="getValues"}?name=customerpricegroups',
    		autoLoad: true,
    		remoteSort: true,
    		reader: new Ext.data.JsonReader({
    			root: 'articles',
    			totalProperty: 'count',
    			id: 'id',
    			fields: [
    				'id', 'name', 'netto', 'active'
    			]
    		})
    	});
    	var groupgrid = new Ext.grid.EditorGridPanel({
    		id:'groupgrid',
    		title:'Preisgruppen',
    		closable:false,
    		store: groupstore,
    		autoScroll: true,
    		//clicksToEdit:1,
    		selModel: new Ext.grid.RowSelectionModel({ singleSelect: true }),
    		cm: new Ext.grid.ColumnModel([
    			{ id:'name', dataIndex: 'name', header: "Name", width: 200, sortable: true, editor: new Ext.form.TextField({ allowBlank: false })},
    			{ id:'netto', dataIndex: 'netto', header: "Eingabe-Modus", width: 200, sortable: true, renderer: function(v){ return v?'Netto':'Brutto' }, editor: new Ext.form.ComboBox({
    				store: new Ext.data.SimpleStore({
    					fields: ['id', 'name'],
    					data : [[0, 'Brutto'],[1, 'Netto']]
    				}),
    				valueField:'id',
    				displayField:'name',
    				mode: 'local',
    				value: 0,
    				selectOnFocus:true,
    				allowBlank: true,
    				typeAhead: false,
    				triggerAction: 'all',
    				editable:false
    			})},
    			{ id:'active', dataIndex: 'active', header: "Aktiv", width: 200, sortable: true, renderer: function(v){ return v?'Ja':'Nein'}, editor: new Ext.form.ComboBox({
    				store: new Ext.data.SimpleStore({
    					fields: ['id', 'name'],
    					data : [[0, 'Nein'],[1, 'Ja']]
    				}),
    				valueField:'id',
    				displayField:'name',
    				mode: 'local',
    				value: 0,
    				selectOnFocus:true,
    				allowBlank: true,
    				typeAhead: false,
    				triggerAction: 'all',
    				editable:false
    			})}
    		]),
    		//viewConfig: { forceFit:true },
    		tbar:[{
    			text:'Preisgruppe anlegen',
    			handler: function (){
    				var r = Ext.data.Record.create([]);
                	var c = new r();
                	Ext.getCmp('groupgrid').stopEditing();
                	Ext.getCmp('groupgrid').store.insert(0, c);
                	Ext.getCmp('groupgrid').startEditing(0, 0);
    			}
    		},'-',{
                text:'Preisgruppe editieren',
                handler: function (){
                	if(!Ext.getCmp('groupgrid').selModel.getSelected()) return;
     				var pricegroupID = Ext.getCmp('groupgrid').selModel.getSelected().data.id;
     				if(!pricegroupID) return;
     				var netto = Ext.getCmp('groupgrid').selModel.getSelected().data.netto;

     				Ext.getCmp('usergrid2').store.baseParams.netto = netto;
     				Ext.getCmp('usergrid').store.load();
     				Ext.getCmp('usergrid2').store.baseParams.pricegroupID = pricegroupID;
     				Ext.getCmp('usergrid2').store.load();
     				Ext.getCmp('grid').store.baseParams.pricegroupID = pricegroupID;
     				Ext.getCmp('grid').store.baseParams.netto = netto;
     				Ext.getCmp('grid').store.load();
     				Ext.getCmp('pricescale').store.baseParams.pricegroupID = pricegroupID;
     				Ext.getCmp('pricescale').store.baseParams.netto = netto;
     				Ext.getCmp('pricescale').store.load();

                	Ext.getCmp('users').enable();
    				Ext.getCmp('prices').enable();
    				Ext.getCmp('tabs').activate(1);
                }
            },'-',{
    			text:'Preisgruppen löschen',
    			handler: function (a, b, c){
    				if(!Ext.getCmp('groupgrid').selModel.getSelected())
                		return;
    				Ext.MessageBox.confirm('', 'Wollen Sie wirklich diese Preisgruppe löschen?', function(r){
    					if(r=='yes')
    					{
    						var pricegroupID = Ext.getCmp('groupgrid').selModel.getSelected().id;
    						Ext.getCmp('groupgrid').store.load({ params:{ "delete": pricegroupID}});
    						if(pricegroupID==Ext.getCmp('usergrid2').store.baseParams.pricegroupID)
    						{
    							Ext.getCmp('users').disable();
    							Ext.getCmp('prices').disable();
    						}
    					}
    				});
    			}
    		}],
    		buttonAlign:'right',
    		buttons: [{
    			text: 'Speichern',
    			handler: function(){
    				Ext.getCmp('users').disable();
    				Ext.getCmp('prices').disable();
    				Ext.getCmp('groupgrid').store.each(function(record){
    					new Request({ method: 'post', url: '{url action="savePricegroups"}', async: false, data: record.data}).send();
						Ext.MessageBox.alert('Status', 'Einstellungen gespeichert.');

    				});
    				Ext.getCmp('groupgrid').store.commitChanges();
    				Ext.getCmp('groupgrid').store.load();
    			}
    		}]
    	});
    	groupstore.load({ params:{ start:0, limit:25}});

    	var cols = [
    		{ id:'customernumber', dataIndex: 'customernumber', header: "Kundennummer", width: 100, sortable: true},
    		{ id:'customergroup', dataIndex: 'customergroup', header: "Kundengruppe", width: 100, sortable: true},
    		{ id:'company', dataIndex: 'company', header: "Firma", width: 120, sortable: true},
    		{ id:'firstname', dataIndex: 'firstname', header: "Vorname", width: 120, sortable: true},
    		{ id:'lastname', dataIndex: 'lastname', header: "Nachname", width: 120, sortable: true},
    		{ id:'options', dataIndex: 'options', header: "Optionen", width: 100, sortable: true, renderer: function (value, p, r){
    			{literal}
                return String.format(
    				'<a class="ico pencil_arrow" style="cursor:pointer" onclick="parent.loadSkeleton({2},false,{3})"></a>',
    				r.data.id,
    				"'"+r.data.lastname+"'",
    				"'userdetails'",
    				"{'user':"+r.data.id+"}"
    			);
                {/literal}
    		}},
    	];

    	var userstore = new Ext.data.Store({
    		url: '{url action="getValues"}?name=users',
    		autoLoad: true,
    		remoteSort: true,
    		reader: new Ext.data.JsonReader({
    			root: 'articles',
    			totalProperty: 'count',
    			id: 'id',
    			fields: [
    				'id', 'customernumber', 'email', 'company', 'firstname', 'lastname', 'customergroup', 'config'
    			]
    		})
    	});
    	var usergrid = new Ext.grid.GridPanel({
    		id: 'usergrid',
    		title: 'Verfügbare Kunden',
    		closable: false,
    		store: userstore,
    		region:'west',
    		margins: '5 0 5 0',
    		width: '50%',
    		minSize: 100,
    		ddGroup: 'secondGridDDGroup',
    		enableDragDrop: true,
    		stripeRows: true,
    		columns: cols,
    		//viewConfig: { forceFit:true },
    		bbar: new Ext.PagingToolbar({
                pageSize: 25,
                store: userstore,
                displayInfo: true,
                displayMsg: '{literal}Zeige Eintrag {0} bis {1} von {2}{/literal}',
                items:[
                    '-', 'Suche: ',
    	            {
    	            	xtype: 'textfield',
    	            	id: 'usersearch',
    	            	selectOnFocus: true,
    	            	width: 120,
    	            	listeners: {
    		            	'render': { fn:function(ob){
    		            		ob.el.on('keyup', function(){
    		            			var search = Ext.getCmp("usersearch");
    							    userstore.baseParams["search"] = search.getValue();
    							    userstore.load({ params:{ start:0, limit:25}});
    		            		}, this, { buffer:500});
    		            	}, scope:this}
    	            	}
    	            }
                ]
            }),
    		listeners: {
    			'rowdblclick': { fn:function(grid, rowIndex, e){
    				var record = Ext.getCmp("usergrid").store.getAt(rowIndex);
    				Ext.getCmp("usergrid").store.remove(record);
    				Ext.getCmp("usergrid2").store.add(record);
    			}, scope:this}
    		}
    	});
    	userstore.load({ params:{ start:0, limit:25}});

    	var userstore2 = new Ext.data.Store({
    		url: '{url action="getValues"}?name=users',
    		autoLoad: true,
    		remoteSort: true,
    		reader: new Ext.data.JsonReader({
    			root: 'articles',
    			totalProperty: 'count',
    			id: 'id',
    			fields: [
    				'id', 'customernumber', 'email', 'company', 'firstname', 'lastname', 'customergroup'
    			]
    		})
    	});
    	var usergrid2 = new Ext.grid.GridPanel({
    		id:'usergrid2',
    		title: 'Ausgewählte Kunden',
    		closable:false,
    		store: userstore2,
    		region:'center',
    		margins: '5 0 5 0',
    		minSize: 100,
    		ddGroup: 'firstGridDDGroup',
    		enableDragDrop: true,
    		stripeRows: true,
    		columns: cols,
    		//viewConfig: { forceFit:true },
    		bbar: new Ext.PagingToolbar({
                pageSize: 25,
                store: userstore2,
                displayInfo: true,
                displayMsg: '{literal}Zeige Eintrag {0} bis {1} von {2}{/literal}',
                items:[
                    '-', 'Suche: ',
    	            {
    	            	xtype: 'textfield',
    	            	id: 'usersearch2',
    	            	selectOnFocus: true,
    	            	width: 120,
    	            	listeners: {
    		            	'render': { fn:function(ob){
    		            		ob.el.on('keyup', function(){
    		            			var search = Ext.getCmp("usersearch2");
    							    userstore2.baseParams["search"] = search.getValue();
    							    userstore2.load({ params:{ start:0, limit:25}});
    		            		}, this, { buffer:500});
    		            	}, scope:this}
    	            	}
    	            }
                ]
            }),
    		listeners: {
    			'rowdblclick': { fn:function(grid, rowIndex, e){
    				var record = Ext.getCmp("usergrid2").store.getAt(rowIndex);
    				Ext.getCmp("usergrid2").store.remove(record);
    				Ext.getCmp("usergrid").store.add(record);
    			}, scope:this}
    		}
    	});
    	userstore2.load({ params:{ start:0, limit:25}});

    	var store = new Ext.data.Store({
    		url: '{url action="getArticles"}',
    		autoLoad: true,
    		remoteSort: true,
    		reader: new Ext.data.JsonReader({
    			root: 'articles',
    			totalProperty: 'count',
    			id: 'ordernumber',
    			fields: [
    				'ordernumber', 'name', 'pricegroup', 'price', 'defaultprice', 'tax', 'config'
    			]
    		})
    	});
    	var grid = new Ext.grid.GridPanel({
    		id:'grid',
    		closable:false,
    		store: store,
    		region:'west',
    		margins: '5 0 5 0',
    		autoScroll: true,
    		padding: 0,
    		width: 600,
    		minSize: 100,
    		selModel: new Ext.grid.RowSelectionModel({ singleSelect: true}),
    		cm: new Ext.grid.ColumnModel([
    			{ dataIndex: 'ordernumber', header: "Bestellnummer", width: 100, sortable: true},
    			{ dataIndex: 'name', header: "Artikelname", width: 200, sortable: true},
    			{ dataIndex: 'defaultprice', header: "Standardpreis", width: 100, sortable: true, align: 'right'},
    			{ dataIndex: 'price', header: "Preis", width: 100, sortable: true, align: 'right'}
    		]),
    		//viewConfig: { forceFit:true },
    		bbar: new Ext.PagingToolbar({
                pageSize: 25,
                store: store,
                displayInfo: true,
                displayMsg: '{literal}Zeige Eintrag {0} bis {1} von {2}{/literal}',
                items:[
                    '-', 'Suche: ',
    	            {
    	            	xtype: 'textfield',
    	            	id: 'search',
    	            	selectOnFocus: true,
    	            	width: 120,
    	            	listeners: {
    		            	'render': { fn:function(ob){
    		            		ob.el.on('keyup', function(){
    		            			var search = Ext.getCmp("search");
    							    store.baseParams["search"] = search.getValue();
    							    store.load({ params:{ start:0, limit:25}});
    		            		}, this, { buffer:500});
    		            	}, scope:this}
    	            	}
    	            }
                ]
            }),
    		tbar:[{
    			text:'Preis bearbeiten',
    			handler: function (){
    				if(!Ext.getCmp('grid').selModel.getSelected())
                		return;
    				var record = Ext.getCmp('grid').selModel.getSelected();
    				Ext.getCmp('pricescale').store.baseParams["ordernumber"] = record.data.ordernumber;
    				Ext.getCmp('pricescale').store.baseParams["pricegroup"] = record.data.pricegroup;
    				Ext.getCmp('pricescale').store.baseParams["tax"] = record.data.tax;
    				Ext.getCmp('pricescale').store.baseParams["config"] = record.data.config;
    				Ext.getCmp('pricescale').store.load();
    			},
    			iconCls:'pencil'
    		},'-',{
    			text:'Preis löschen',
    			handler: function (a, b, c){
    				Ext.MessageBox.confirm('', 'Wollen Sie wirklich den Preis löschen?', function(r){
    					if(!Ext.getCmp('grid').selModel.getSelected())
                			return;
    					if(r=='yes')
    					{
    						var ordernumber = Ext.getCmp('grid').selModel.getSelected().id;
    						store.load({ params:{ "delete": ordernumber}});
    					}
    				});
    			},
    			iconCls:'delete'
    		}],
    		listeners: {
    			'rowdblclick': { fn:function(grid, rowIndex, e){
    				var record = grid.store.getAt(rowIndex);
    				Ext.getCmp('pricescale').store.baseParams["ordernumber"] = record.data.ordernumber;
    				Ext.getCmp('pricescale').store.baseParams["pricegroup"] = record.data.pricegroup;
    				Ext.getCmp('pricescale').store.baseParams["tax"] = record.data.tax;
    				Ext.getCmp('pricescale').store.baseParams["config"] = record.data.config;
    				Ext.getCmp('pricescale').store.load();
    			}, scope:this}
    		}
    	});
    	store.load({ params:{ start:0, limit:25}});

    	function renderPrice (value)
    	{
    		if(value&&value.toFloat())
    			return value.toFloat().toFixed(2).split(".").join(",");
    		else
    			return "";
    	}
    	var decimalPrecision = 0;
      	var minChange = 1;
       	var startValue = 1;
    	var pricescale = {
       		id: 'pricescale',
       		xtype: 'editorgrid',
       		autoScroll: true,
       		bodyStyle:'padding:0px',
       		region:'center',
    		margins: '5 0 5 0',
       		clicksToEdit:1,
       		store: new Ext.data.Store({
       			url: '{url action="getPricescale"}',
       			baseParams: { startValue: startValue, minChange: minChange},
       			//autoLoad: true,
       			reader: new Ext.data.JsonReader({
       				root: 'articles',
       				totalProperty: 'count',
       				id: 'to',
       				fields: ['from', 'to', 'price',/* 'pseudoprice', 'baseprice',*/ 'percent']
       			})
       		}),
       		cm: new Ext.grid.ColumnModel([
    	   		{ id:'from', dataIndex: 'from', header: "Von", align: 'right', width: 150, sortable: false, editor: new Ext.form.NumberField({ allowBlank: true, decimalPrecision : decimalPrecision, decimalSeparator: ',', readOnly:true, allowNegative: false}),renderer: function(value, p, record){
    	   			if(value&&value.toFloat())
    	   				return value.toFloat().toFixed(decimalPrecision).split(".").join(",");
    	   			else
    	   				return (0).toFixed(decimalPrecision).split(".").join(",");
    	   		}},
    	   		{ id:'to', dataIndex: 'to', header: "Bis", align: 'right', width: 150, sortable: false, editor: new Ext.form.NumberField({
    	   			allowBlank: true,
    	   			decimalPrecision : decimalPrecision,
    	   			decimalSeparator: ',',
    	   			allowNegative: false,
    	   			validator: function(value)
    	   			{
    	   				if(Ext.getCmp('pricescale').store.baseParams.config)
    	   				{
    	   					return "Preisstaffeln werden vom Artikel-Konfigurator nicht unterstützt";
    	   				}
    	   				if(this.gridEditor.row)
    	   				{
    	   					this.minValue = this.gridEditor.record.get("from");
    	   				}
    	   				else
    	   				{
    	   					this.minValue = startValue;
    	   				}
    	   				this.minValue = this.minValue.toFloat().toFixed(decimalPrecision);
    	   				/*
    	   				if(value < this.minValue)
    	   				{
    	   					return String.format(this.minText, this.minValue);
    	   				}
    	   				*/
    	   				return true;
    	   			}
    	   		}),renderer: function(value, p, record){
    	   			if(value&&value.toFloat())
    	   				return value.toFloat().toFixed(decimalPrecision).split(".").join(",");
    	   			else
    	   				return "beliebig";
    	   		}},
    	   		{ id:'price', dataIndex: 'price', header: "Verkaufspreis", align: 'right', width: 150, sortable: false, editor: new Ext.form.NumberField({ allowBlank: false, decimalPrecision : 2, decimalSeparator: ',', allowNegative: false}),renderer: renderPrice},
    	   		{ id:'percent', dataIndex: 'percent', header: "Prozentrabatt", width: 150, align: 'right', sortable: false, editor: new Ext.form.NumberField({ allowBlank: true, decimalPrecision : 2, decimalSeparator: ',', allowNegative: false, maxValue: 99.99}),renderer: function(value, p, record){
    	   			if(value&&value.toFloat())
    	   				return value.toFloat().toFixed(2).split(".").join(",") + ' %';
    	   			else
    	   				return "";
    	   		}}
    	   		//,{ id:'pseudoprice', dataIndex: 'pseudoprice', header: "Pseudopreis", width: 150, align: 'right', sortable: false, editor: new Ext.form.NumberField({ allowBlank: true, decimalPrecision : 2, decimalSeparator: ','}),renderer: renderPrice},
    	   		//{ id:'baseprice', dataIndex: 'baseprice', header: "Einkaufspreis", width: 150, align: 'right', sortable: false, editor: new Ext.form.NumberField({ allowBlank: true, decimalPrecision : 2, decimalSeparator: ','}),renderer: renderPrice}
       		]),
       		listeners : {
    	   		"afteredit" : { fn: function(e)
    	   		{
    	   			if(e.field=="price")
    	   			{
    	   				if(e.row!=0)
    	   				{
    	   					var firstRecord = e.grid.store.getAt(0);
    	   					var firstPrice = firstRecord.get("price");
    	   					var record = e.grid.store.getAt(e.row);
    	   					var price = record.get("price");
    	   					if(firstPrice>price)
    	   					{
    	   						var percent = (firstPrice-price)/firstPrice*100;
    	   						percent = percent.toFixed(2);
    	   						record.set("percent",percent);
    	   					}
    	   				}
    	   			}
    	   			else if(e.field=="percent")
    	   			{
    	   				if(e.row==0)
    	   				{
    	   					var record = e.grid.store.getAt(0);
    	   					record.set("percent","");
    	   				}
    	   				else
    	   				{
    		   				var firstRecord = e.grid.store.getAt(0);
    		   				var record = e.grid.store.getAt(e.row);
    		   				var price = firstRecord.get("price");
    		   				var percent = record.get("percent");
    		   				var price = price/100*(100-percent);
    		   				price = price.toFixed(2);
    		   				record.set("price",price);
    	   				}
    	   			}
    	   			else if(e.field=="to")
    	   			{
    	   				if(e.grid.store.data.keys[e.row+1])
    	   				{
    	   					while(e.grid.store.data.keys[e.row+1])
    	   					{
    	   						var recordID = e.grid.store.data.keys[e.row+1]
    	   						var record = e.grid.store.getById(recordID);
    	   						if(record.get("to")&&record.get("to")<=e.value)
    	   						{
    	   							e.grid.store.remove(record);
    	   						}
    	   						else
    	   						{
    	   							record.set("from",e.value+minChange);
    	   							break;
    	   						}
    	   					}
    	   				}
    	   				else if(e.value)
    	   				{
    	   					var r = Ext.data.Record.create([
    		   					{ name: 'from'},
    		   					{ name: 'to'},
    		   					{ name: 'price'},
    		   					{ name: 'percent'}
    	   					]);
    	   					var c = new r({
    	   						from: e.value+minChange,
    	   						to: "",
    	   						value: "",
    	   						factor: ""
    	   					});
    	   					e.grid.stopEditing();
    	   					e.grid.store.insert(e.row+1, c);
    	   					e.grid.startEditing(e.row+1, 1);
    	   				}
    	   			}
    	   		}, scope:this}
       		},
       		tbar: [{
       			text: 'Letzte Staffel entfernen',
       			iconCls:'delete',
       			handler : function()
       			{
       				var pricescale = Ext.getCmp('pricescale');
       				var count = pricescale.store.getCount();
       				if(count&&count>1)
       				{
       					var recordID = pricescale.store.data.keys[count-2]
       					var record = pricescale.store.getById(recordID);
       					record.set("to","");
       					pricescale.store.remove(pricescale.store.data.items[count-1]);
       				}
       			}
       		}]
       	};

       	var prices = {
       		title:'Preiseingabe',
       		layout:'border',
       		id: 'prices',
       		disabled : true,
    		defaults: {
    		    collapsible: false,
    		    split: true
    		},
    		items: [grid,pricescale],
       		buttonAlign:'right',
            buttons: [{
                text: 'Speichern',
                handler: function(){
                	Ext.getCmp('pricescale').store.each(function(record){
                		var params = record.data;
                		params.pricegroupID = Ext.getCmp('pricescale').store.baseParams.pricegroupID;
                		params.ordernumber = Ext.getCmp('pricescale').store.baseParams.ordernumber;
                		if(!Ext.getCmp('pricescale').store.baseParams.netto)
                			params.tax = Ext.getCmp('pricescale').store.baseParams.tax;
                		new Request({ method: 'post', url: '{url action="savePricescale"}', async: false, data: params}).send();
                	});
                	Ext.getCmp('pricescale').store.commitChanges();
    	        }
            }]
       	}

       	var users = {
       		title:'Kundenauswahl',
       		layout:'border',
       		id: 'users',
       		disabled : true,
    		defaults: {
    		    collapsible: false,
    		    split: true
    		},
    		items: [usergrid, usergrid2],
       		buttonAlign:'right',
            buttons: [{
                text: 'Speichern',
                handler: function(){
                	var pricegroupID = Ext.getCmp('usergrid2').store.baseParams.pricegroupID;
                	var userIDs = [];
                	userstore2.each(function(record, i){
    					userIDs[i] = record.data.id;
    				});
    				new Request({ method: 'post', url: '{url action="saveUsers"}', async: false, data: { 'userIDs[]': userIDs, pricegroupID: pricegroupID}}).send();
    				Ext.getCmp('usergrid').store.load();
    				Ext.getCmp('usergrid2').store.load();
    	        }
            }]
       	}

       	var tabs = new Ext.TabPanel({
       		id: 'tabs',
        	region:'center',
            enableTabScroll:true,
            deferredRender:false,
            activeTab:0,
            defaults: { autoScroll:true},
            plugins: new Ext.ux.TabCloseMenu(),
            items: [groupgrid, users, prices]
        });

    	var viewport = new Ext.Viewport({
    		layout:'fit',
    		items: tabs
    	});


    	var firstGridDropTargetEl =  usergrid.getView().scroller.dom;
    	var firstGridDropTarget = new Ext.dd.DropTarget(firstGridDropTargetEl, {
    		ddGroup    : 'firstGridDDGroup',
    		notifyDrop : function(ddSource, e, data){
    			var records =  ddSource.dragData.selections;
    			Ext.each(records, ddSource.grid.store.remove, ddSource.grid.store);
    			usergrid.store.add(records);
    			//usergrid.store.sort('customernumber', 'ASC');
    			return true
    		}
    	});

    	var secondGridDropTargetEl = usergrid2.getView().scroller.dom;
    	var secondGridDropTarget = new Ext.dd.DropTarget(secondGridDropTargetEl, {
    		ddGroup    : 'secondGridDDGroup',
    		notifyDrop : function(ddSource, e, data){
    			var records =  ddSource.dragData.selections;
    			Ext.each(records, ddSource.grid.store.remove, ddSource.grid.store);
    			usergrid2.store.add(records);
    			//usergrid2.store.sort('customernumber', 'ASC');
    			return true
    		}
    	});

    });
</script>
</head>
<body>

 </body>
</html>