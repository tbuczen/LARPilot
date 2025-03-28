
import { startStimulusApp } from '@symfony/stimulus-bundle';

import Folder_browser_controller from "./controllers/integrations/folder_browser_controller.js";
import GoogleFilePickerController from "./controllers/integrations/google_file_picker_controller.js";

const app = startStimulusApp();
app.register('folder-browser', Folder_browser_controller);
app.register("google-file-picker", GoogleFilePickerController);
