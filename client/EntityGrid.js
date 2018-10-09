window.grids = {}; //For debugging purposes
/**
 * @extends {Set}
 */
class GridLocalStorage {
	id;

	/**
	 * @type {Set}
	 */
	data;

	constructor(id) {
		this.id = this.constructor.name + id;
		let data = window.localStorage.getItem(this.id);
		this.data = new Set(data ? JSON.parse(data) : []);
		window.addEventListener("beforeunload", this.persist.bind(this), true);
	}

	persist() {
		window.localStorage.setItem(this.id, JSON.stringify(this.values()));
		return this;
	}

	set(key, on) {
		return on ? this.add(key) : this.remove(key);
	}

	add(key) {
		return this.data.add(key);
	}

	remove(key) {
		return this.data.delete(key);
	}

	size() {
		return this.data.size;
	}

	clear() {
		return this.data.clear();
	}

	has(key) {
		return this.data.has(key);
	}

	values() {
		return Array.from(this.data.values());
	}
}

export default class EntityGrid {

	static CHECKBOX_SELECTOR = '[data-row-select]';
	static CHECKBOX_MODE_SELECTOR = '[data-row-select=exclude]';
	static CHECKBOX_ALL_SELECTOR = '[data-row-select=all]';
	static CHECKBOX_ROW_SELECTOR = '[data-row-select]:not([data-row-select=all]):not([data-row-select=exclude])';

	/**
	 * @type {GridLocalStorage|Set}
	 */
	ids;

	/**
	 * @type {GridLocalStorage|Set}
	 */
	status;

	/**
	 * @type {HTMLDivElement}
	 */
	element;

	/**
	 * @type {string}
	 */
	control;

	/**
	 * @param {HTMLDivElement} element
	 */
	constructor(element) {
		window.grids[element.id] = this;
		if (!element.id) {
			throw Error('EntityGrid element must have id.');
		}
		element.dataGrid = this;
		this.element = element;
		this.control = element.dataset.control;
		this.ids = new GridLocalStorage(element.id + '-ids');
		this.status = new GridLocalStorage(element.id + '-status');
		element.addEventListener('change', this.onChange.bind(this));
		element.addEventListener('destroy', this.destroy.bind(this));
		const observer = new MutationObserver(this.observe.bind(this));
		observer.observe(element, {childList: true, subtree: true});
		this.init();
	}

	selecting = null;

	onMouseDown(checkbox, e) {
		this.selecting = !checkbox.checked;
		e.preventDefault();
	}

	/**
	 *
	 * @param {HTMLInputElement} checkbox
	 * @param e
	 */
	onMouseMove(checkbox, e) {
		if (this.selecting !== null) {
			if (checkbox.checked !== this.selecting) {
				checkbox.click();
			}
			e.preventDefault();
		}
	}

	onMouseUp(checkbox, e) {
		if (this.selecting !== null) {
			this.selecting = null;
			e.preventDefault();
		}
	}

	/**
	 * @param {Number[]} ids
	 */
	deselect(ids) {
		for (let id of ids)this.ids.remove(id);
	}

	destroy() {
		delete window.grids[this.element.id];
	}

	getSelects() {
		return this.element.querySelectorAll(EntityGrid.CHECKBOX_ROW_SELECTOR);
	}

	select(checkbox) {
		const value = Number(checkbox.dataset.rowSelect) || checkbox.dataset.rowSelect, checked = checkbox.checked;
		if (value === 'exclude') {
			this.ids.clear();
			this.status.set('exclude', checked);
			for (let e of this.getSelects())e.checked = checked;
			this.element.querySelector(EntityGrid.CHECKBOX_ALL_SELECTOR).checked = checked;
		} else if (value === 'all') {
			for (let e of this.getSelects()) {
				e.checked = checked;
				this.select(e);
			}
		} else {
			const exclude = this.status.has('exclude');
			this.ids.set(value, exclude != checked);
		}
	}

	init() {
		const exclude = this.status.has('exclude');
		for (let e of this.getSelects()) {
			const cell = e.closest('.grid-cell');
			cell.addEventListener('mousedown', this.onMouseDown.bind(this, e));
			cell.addEventListener('mousemove', this.onMouseMove.bind(this, e));
			cell.addEventListener('mouseup', this.onMouseUp.bind(this, e));
			e.checked = exclude != this.ids.has(e.dataset.rowSelect);
		}
		this.update();
	}

	count = true;

	update() {
		const exclude = this.status.has('exclude');
		const selection = this.element.querySelector('[data-grid-selection]');
		const counters = this.element.querySelectorAll('[data-grid-selection-count]');
		const count = exclude ? selection.dataset.gridSelection - this.ids.size() : this.ids.size();
		for (let c of counters)c.textContent = count;
		if (Boolean(this.count) !== Boolean(count)) {
			for (let a of this.element.querySelectorAll('a[data-control*=-groupAction-]')) a.classList[count ? 'remove' : 'add']('disabled');
		}
		this.count = count;
		this.element.querySelector(EntityGrid.CHECKBOX_MODE_SELECTOR).checked = exclude;
		this.updateGroupActions();
	}

	onChange(event) {
		const checkbox = event.target;
		if (checkbox.matches(EntityGrid.CHECKBOX_SELECTOR)) {
			this.select(checkbox);
			this.update();
		}
	}

	updateGroupActions() {
		for (let a of this.element.querySelectorAll('.grid-group-actions a')) {
			a.dataset.post = JSON.stringify({
				exclude: Number(this.status.has('exclude')),
				ids: this.ids.values()
			});
		}
	}

	/**
	 *
	 * @param {MutationRecord[]} mutationsList
	 * @param {MutationObserver} observer
	 */
	observe(mutationsList, observer) {
		for (var mutation of mutationsList) {
			if (mutation.type == 'childList') {
				if (mutation.target.matches('.grid-table-body,.grid-header,.grid-search-row')) {
					this.init();
				}
			}
		}
	}

}

class GridExtension {
	constructor(naja) {
		naja.addEventListener('load', this.initGrids.bind(this));
		naja.addEventListener('success', this.success.bind(this));
	}

	initGrids() {
		for (let e of document.querySelectorAll('[data-entity-grid]')) e.dataGrid || new EntityGrid(e);
	}

	success({response}) {
		if (response && response.grid) {
			for (var id in response.grid) {
				if (response.grid.hasOwnProperty(id)) {
					const gridElement = document.getElementById(id);
					if (gridElement) {
						const grid = gridElement.dataGrid;
						const actions = response.grid[id];
						for (var action in actions) {
							if (actions.hasOwnProperty(action) && typeof grid[action] === 'function') {
								grid[action](actions[action]);
							}
						}
					}
				}
			}
		}
	}
}

export {GridLocalStorage, EntityGrid, GridExtension};