import { Injectable } from '@angular/core';

// This interface is optional, showing how you can add strong typings for custom globals.
// Just use "Window" as the type if you don't have custom global stuff
export interface TetraCustomWindow extends Window {
    // __custom_global_stuff: string;
}

function getWindow (): any {
    return window;
}

@Injectable()
export class TetraWindowService {
    get nativeWindow (): TetraCustomWindow {
        return getWindow();
    }
}
