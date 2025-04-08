import { Component, Input, Output, EventEmitter } from '@angular/core';
import { TetraFieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { CoreService } from '@tetra/core.service';
import { Observable, Subject, EMPTY, throwError, finalize } from "rxjs";

@Component({
	standalone: true,
  selector: '.field.file',
  imports: [ CommonModule, FormsModule ],
  templateUrl: './field-file.component.html',
  styleUrl: './field-file.component.scss'
})
export class TetraFieldFileComponent extends TetraFieldComponent {
  @Input() dir: string = '';
  uploadProgress:number = 0;
  uploadSub: any = null;
  file: any = null;



  @Input() requiredFileType:string = '';

    constructor(private core: CoreService) {
      super();
    }

    fileSubmit(type: string, action: string, payload: any = {}) {
      if (this.model) {
        const formData = new FormData();
        formData.append("file", this.model);
        for (let key in payload) {
          formData.append(key.toString(), payload[key]);
        }

        // if (this.model.name){
        //   let parts = this.model.name.split('.');
        //   const extension = parts.pop();
        //   formData.append("extension", extension);
        //   const name = parts.join('.');
        //   formData.append("name", name);
        // }
        // if (this.dir){
        //   console.log('dir', this.dir);
        //   formData.append("directory", this.dir);
        // }
        console.log('upload', formData);
        return this.core.post(type, action, formData);
        /*
        const upload$ = this.http.post("/core/file/upload", formData, {
          reportProgress: true,
          observe: 'events'
        })
        .pipe(
          finalize(() => this.reset())
        );

        this.uploadSub = upload$.subscribe((event: any) => {
          const body = event.body;
          // console.log('event', event);
          if (event && event.type && event.type == HttpEventType.UploadProgress && event.total && event.loaded) {
            this.uploadProgress = Math.round(100 * (event.loaded / event.total));
          }
          if (event && event.body && event.body.data) {
            console.log('complete', event.body.data);
          }
        })
        return upload$.toPromise();
        */
      }
      return new Promise((resolve, reject) => {
        reject('no file');
      });
    }

    onFileSelected(event: any) {
      console.log('on file select', event.target.files[0]);
      const file = event.target.files[0];
      this.model = file;

      this.update();
    }
}
