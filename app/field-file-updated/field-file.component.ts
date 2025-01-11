import { Component, Input, Output, EventEmitter, ChangeDetectorRef } from '@angular/core';
import { TetraFieldComponent } from '../field/field.component';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpHeaders, HttpEventType } from '@angular/common/http';
import { Observable, Subject, EMPTY, throwError, finalize } from "rxjs";

@Component({
  selector: '.field.file',
  standalone: true,
  imports: [ CommonModule, FormsModule ],
  templateUrl: './field-file.component.html',
  styleUrl: './field-file.component.scss'
})

export class TetraFieldFileComponent extends TetraFieldComponent {
  fileName = '';
  uploadProgress:number = 0;
  uploadSub: any = null;
  file: File|null = null;
  path: string = '';
  preview: any = null;
  name: string = '';
  extension: any = '';
  alt: string = '';
  ready: boolean = false;
  @Input() override type: string = 'any';
  @Input() requiredFileType:string = '';

    constructor(private http: HttpClient, private cdref: ChangeDetectorRef) {
      super();
    }

    onFileSelected(event: any) {
      const self = this;
      self.preview = '';
      self.name = '';
      self.alt = '';
      this.file = event.target.files[0];
      if (this.file) {
        let name = this.file.name.split('.');
        this.extension = name.pop();
        this.name = name.join('.');
        this.name = this.name.replace(/-+/gm, ' ');
        const words = this.name.split(' ');

        for (let i = 0; i < words.length; i++) {
            words[i] = words[i][0].toUpperCase() + words[i].substr(1);
        }
        this.name = words.join(' ');
        var reader = new FileReader();
        reader.onload = function(){
          if (reader.result){
            self.preview = reader.result.toString();
          }
        };
        reader.readAsDataURL(event.target.files[0]);
      }
      this.isReady(event);
    }

    src(){
      if (this.path !== '') {
        return this.path;
      } else if (this.preview) {
        return this.preview.toString();
      } else {
        return '';
      }
    }

    upload() {
        if (this.file) {
            this.fileName = this.file.name;
            const formData = new FormData();
            formData.append("file", this.file);
            formData.append('name', this.name);
            formData.append('extension', this.extension);
            if (this.alt) {
              formData.append('alt', this.alt);
            }

            const upload$ = this.http.post("/core/asset/create", formData, {
                reportProgress: true,
                observe: 'events'
            })
            .pipe(
                finalize(() => this.reset())
            );

            this.uploadSub = upload$.subscribe((event: any) => {
              const body = event.body;
              if (event && event.type && event.type == HttpEventType.UploadProgress && event.total && event.loaded) {
                this.uploadProgress = Math.round(100 * (event.loaded / event.total));
              }
              if (body && body.data && body.data.asset && body.data.asset.status && body.data.asset.status == 'completed') {
                const asset = body.data.asset;
                this.path = asset.relative_path;
                this.model = asset;
                this.update();
              }
            })
        }
    }

  cancelUpload() {
    this.uploadSub.unsubscribe();
    this.reset();
  }

  reset() {
    this.uploadProgress = 0;
    this.uploadSub = null;
  }

  isReady(event: any) {
    if (this.type == 'image') {
      if (this.file) {
        if (!this.alt) {
          this.setError('Enter an alt text description of this image.');
          this.ready = false;
          return false;
        }
      }
    }
    if (!this.file) {
      this.setError('');
      this.ready = false;
      return false;
    }
    if (!this.name) {
      this.setError('Name this image');
      this.ready = false;
      return false;
    }
    this.setError('');
    this.ready = true;
    return true;
  }
}
