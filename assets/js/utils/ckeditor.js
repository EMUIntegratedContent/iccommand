/**
 * The classic CKEditor used by every rich-text field.
 *
 * CKEditor 5 dropped its predefined builds (@ckeditor/ckeditor5-build-classic) after v44, so the
 * editor is assembled here from the `ckeditor5` package. The plugin list mirrors the old classic
 * build, minus the upload and cloud plugins (CKBox, CKFinder, Easy Image), which were never
 * configured. Heading, Image, Table, etc. stay loaded even though no toolbar shows them, so content
 * already saved with that markup keeps it when edited instead of being stripped.
 */
import {
	Autoformat,
	BlockQuote,
	Bold,
	ClassicEditor as BaseClassicEditor,
	Essentials,
	Heading,
	Image,
	ImageCaption,
	ImageStyle,
	ImageToolbar,
	Indent,
	Italic,
	Link,
	List,
	MediaEmbed,
	Paragraph,
	PasteFromOffice,
	Table,
	TableToolbar,
	TextTransformation
} from "ckeditor5"

import "ckeditor5/ckeditor5.css"

export default class ClassicEditor extends BaseClassicEditor {
	static builtinPlugins = [
		Essentials,
		Autoformat,
		Bold,
		Italic,
		BlockQuote,
		Heading,
		Image,
		ImageCaption,
		ImageStyle,
		ImageToolbar,
		Indent,
		Link,
		List,
		MediaEmbed,
		Paragraph,
		PasteFromOffice,
		Table,
		TableToolbar,
		TextTransformation
	]

	static defaultConfig = {
		// CKEditor 5 v44+ requires a license key; "GPL" is the open-source license, the same terms
		// the old predefined build was used under.
		licenseKey: "GPL",
		toolbar: ["bold", "italic", "undo", "redo", "numberedList", "bulletedList", "link"],
		image: {
			toolbar: ["imageStyle:inline", "imageStyle:block", "imageStyle:side", "|", "toggleImageCaption", "imageTextAlternative"]
		},
		table: {
			contentToolbar: ["tableColumn", "tableRow", "mergeTableCells"]
		},
		language: "en"
	}
}
