import { startStimulusApp } from '@symfony/stimulus-bundle';

import AutocompleteController from '@symfony/ux-autocomplete';
import LiveController from '@symfony/ux-live-component';
import Folder_browser_controller from "./controllers/integrations/folder_browser_controller.js";
import GoogleFilePickerController from "./controllers/integrations/google_file_picker_controller.js";
import CustomAutocompleteController from "./controllers/custom-autocomplete_controller.js";
import StoryGraphController from "./controllers/story_graph_controller.js";
import DecisionTreeController from "./controllers/decision_tree_controller.js";
import WysiwygController from "./controllers/wysiwyg_controller.js";
import KanbanController from "./controllers/kanban_controller.js";
import SortableCharacterChoicesController from "./controllers/sortable_character_choices_controller.js";
import TimelineController from "./controllers/timeline_controller.js";

const app = startStimulusApp();
app.register('live', LiveController);
app.register('autocomplete', AutocompleteController);
app.register('folder-browser', Folder_browser_controller);
app.register("google-file-picker", GoogleFilePickerController);
app.register("custom-autocomplete", CustomAutocompleteController);
app.register("story-graph", StoryGraphController);
app.register("decision-tree", DecisionTreeController);
app.register("wysiwyg", WysiwygController);
app.register("kanban", KanbanController);
app.register("sortable-character-choices", SortableCharacterChoicesController);
app.register("timeline", TimelineController);
