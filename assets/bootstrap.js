
import { startStimulusApp } from '@symfony/stimulus-bundle';

import Folder_browser_controller from "./controllers/integrations/folder_browser_controller.js";

const app = startStimulusApp();
// register any custom, 3rd party controllers here
app.register('folder-browser', Folder_browser_controller);
